<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class EmailVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code',
        'attempts',
        'action',
        'expires_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Sinh mã xác thực OTP 6 số mới, lưu dạng hash vào DB và trả về mã plain text duy nhất cho Mailer.
     */
    public static function generate(string $email, string $action, int $expiryMinutes = 15, ?string $ipAddress = null): string
    {
        $normalizedEmail = strtolower(trim($email));

        // Xóa các mã cũ của cùng email và mục đích (đảm bảo single active OTP)
        static::where('email', $normalizedEmail)
            ->where('action', $action)
            ->delete();

        $plainCode = sprintf('%06d', random_int(0, 999999));

        static::create([
            'email' => $normalizedEmail,
            'code' => Hash::make($plainCode),
            'attempts' => 0,
            'action' => $action,
            'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
            'ip_address' => $ipAddress,
        ]);

        return $plainCode;
    }

    /**
     * Xác thực OTP chi tiết: kiểm tra hash, hết hạn, giới hạn số lần thử và hủy mã ngay sau khi dùng (single-use).
     *
     * @return array{success: bool, message: string, reason: string, remaining_attempts?: int}
     */
    public static function verifyDetailed(string $email, string $code, string $action): array
    {
        $normalizedEmail = strtolower(trim($email));
        $normalizedCode = trim((string) $code);

        /** @var self|null $record */
        $record = static::where('email', $normalizedEmail)
            ->where('action', $action)
            ->latest('id')
            ->first();

        // 1. Không tìm thấy mã (chưa yêu cầu, đã hết hạn xóa, hoặc đã được sử dụng trước đó)
        if (! $record) {
            return [
                'success' => false,
                'reason' => 'not_found',
                'message' => 'Mã xác thực không chính xác, đã hết hạn hoặc đã được sử dụng. Vui lòng yêu cầu gửi lại mã mới.',
            ];
        }

        // 2. Kiểm tra thời hạn hiệu lực
        if ($record->expires_at->isPast()) {
            $record->delete();

            return [
                'success' => false,
                'reason' => 'expired',
                'message' => 'Mã xác thực đã hết hạn hiệu lực. Vui lòng yêu cầu gửi lại mã mới.',
            ];
        }

        // 3. Đã vượt quá 5 lần thử sai trước đó
        if ($record->attempts >= 5) {
            $record->delete();

            return [
                'success' => false,
                'reason' => 'max_attempts_exceeded',
                'remaining_attempts' => 0,
                'message' => 'Bạn đã nhập sai mã xác thực quá 5 lần. Mã này đã bị vô hiệu hóa vì lý do an toàn, vui lòng yêu cầu mã mới.',
            ];
        }

        // 4. Kiểm tra mã khớp (hỗ trợ hash bcrypt hoặc legacy plaintext nếu có)
        $isMatch = Hash::check($normalizedCode, $record->code)
            || hash_equals($record->code, $normalizedCode);

        if ($isMatch) {
            // Đúng mã: Xóa ngay lập tức khỏi DB để đảm bảo single-use 100%
            $record->delete();

            return [
                'success' => true,
                'reason' => 'verified',
                'message' => 'Xác thực mã OTP thành công.',
            ];
        }

        // 5. Sai mã: Tăng số lần thử
        $record->increment('attempts');
        $remaining = max(0, 5 - (int) $record->attempts);

        if ($remaining <= 0) {
            $record->delete();

            return [
                'success' => false,
                'reason' => 'max_attempts_exceeded',
                'remaining_attempts' => 0,
                'message' => 'Bạn đã nhập sai mã xác thực quá 5 lần. Mã này đã bị vô hiệu hóa vì lý do an toàn, vui lòng yêu cầu mã mới.',
            ];
        }

        return [
            'success' => false,
            'reason' => 'invalid_code',
            'attempts' => (int) $record->attempts,
            'remaining_attempts' => $remaining,
            'message' => "Mã xác thực không chính xác. Bạn còn {$remaining} lần thử trước khi mã bị khóa.",
        ];
    }

    /**
     * Phương thức xác thực trả về boolean (tương thích ngược hoàn toàn).
     */
    public static function verify(string $email, string $code, string $action): bool
    {
        return static::verifyDetailed($email, $code, $action)['success'];
    }

    /**
     * Kiểm tra xem đã đủ thời gian chờ (cooldown) để gửi lại mã mới hay chưa.
     */
    public static function canResend(string $email, string $action, int $cooldownSeconds = 60): bool
    {
        return static::secondsUntilResend($email, $action, $cooldownSeconds) <= 0;
    }

    /**
     * Số giây còn lại phải chờ trước khi được phép gửi lại mã OTP.
     */
    public static function secondsUntilResend(string $email, string $action, int $cooldownSeconds = 60): int
    {
        $latest = static::where('email', strtolower(trim($email)))
            ->where('action', $action)
            ->latest('id')
            ->first();

        if (! $latest || ! $latest->created_at) {
            return 0;
        }

        $allowedAt = $latest->created_at->copy()->addSeconds($cooldownSeconds);
        $diff = (int) ceil(Carbon::now()->diffInSeconds($allowedAt, false));

        return max(0, $diff);
    }

    /**
     * Kiểm tra giới hạn tần suất gửi OTP theo cả Email và IP (Chống spam email & brute-force IP).
     */
    public static function isRateLimited(
        string $email,
        string $action,
        ?string $ipAddress = null,
        int $maxPerHour = 5,
        int $maxIpPerHour = 15
    ): bool {
        // 1. Giới hạn theo địa chỉ email (mặc định 5 lần / giờ)
        $emailKey = 'otp_hourly_email:'.sha1(strtolower(trim($email)).'|'.$action);
        if (RateLimiter::tooManyAttempts($emailKey, $maxPerHour)) {
            return true;
        }

        // 2. Giới hạn theo địa chỉ IP (mặc định 15 lần / giờ chống spam đa tài khoản)
        if ($ipAddress !== null && $ipAddress !== '') {
            $ipKey = 'otp_hourly_ip:'.sha1($ipAddress.'|'.$action);
            if (RateLimiter::tooManyAttempts($ipKey, $maxIpPerHour)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ghi nhận 1 lần gửi OTP vào bộ đếm rate limiter cho cả Email và IP (giữ trong 3600 giây).
     */
    public static function hitRateLimiter(
        string $email,
        string $action,
        ?string $ipAddress = null,
        int $decaySeconds = 3600
    ): void {
        $emailKey = 'otp_hourly_email:'.sha1(strtolower(trim($email)).'|'.$action);
        RateLimiter::hit($emailKey, $decaySeconds);

        if ($ipAddress !== null && $ipAddress !== '') {
            $ipKey = 'otp_hourly_ip:'.sha1($ipAddress.'|'.$action);
            RateLimiter::hit($ipKey, $decaySeconds);
        }
    }
}
