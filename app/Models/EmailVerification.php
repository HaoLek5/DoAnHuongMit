<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'code',
        'action',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Sinh mã xác thực OTP 6 số mới và lưu vào cơ sở dữ liệu.
     */
    public static function generate(string $email, string $action, int $expiryMinutes = 15): string
    {
        // Xóa các mã cũ của cùng email và mục đích
        static::where('email', $email)
            ->where('action', $action)
            ->delete();

        $code = sprintf('%06d', random_int(0, 999999));

        static::create([
            'email' => $email,
            'code' => $code,
            'action' => $action,
            'expires_at' => Carbon::now()->addMinutes($expiryMinutes),
        ]);

        return $code;
    }

    /**
     * Kiểm tra mã xác thực OTP có chính xác và còn hạn sử dụng không.
     * Nếu hợp lệ, tự động hủy mã để tránh sử dụng lại (single-use).
     */
    public static function verify(string $email, string $code, string $action): bool
    {
        $record = static::where('email', $email)
            ->where('action', $action)
            ->where('code', trim($code))
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (! $record) {
            return false;
        }

        $record->delete();

        return true;
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
        $latest = static::where('email', $email)
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
}
