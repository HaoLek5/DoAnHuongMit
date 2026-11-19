<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationMail;
use App\Models\EmailVerification;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpSecurityEnhancementTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_is_stored_hashed_and_never_in_plaintext(): void
    {
        $email = 'test_hash@haomilk.test';
        $plainCode = EmailVerification::generate($email, 'register', 15, '192.168.1.100');

        $this->assertEquals(6, strlen($plainCode));

        $record = EmailVerification::where('email', $email)->where('action', 'register')->first();
        $this->assertNotNull($record);

        // Đảm bảo không lưu code dạng văn bản thô trong DB
        $this->assertNotEquals($plainCode, $record->code);
        $this->assertTrue(Hash::check($plainCode, $record->code));
        $this->assertEquals('192.168.1.100', $record->ip_address);
        $this->assertEquals(0, $record->attempts);
    }

    public function test_otp_single_use_cannot_be_used_twice(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'single_use@haomilk.test',
        ]);

        $plainCode = EmailVerification::generate($user->email, 'register');

        // Lần 1: Xác thực thành công
        $firstVerify = EmailVerification::verifyDetailed($user->email, $plainCode, 'register');
        $this->assertTrue($firstVerify['success']);

        // OTP phải bị xóa ngay lập tức khỏi DB để đảm bảo single-use
        $this->assertDatabaseMissing('email_verifications', [
            'email' => $user->email,
            'action' => 'register',
        ]);

        // Lần 2: Sử dụng lại cùng mã phải thất bại
        $secondVerify = EmailVerification::verifyDetailed($user->email, $plainCode, 'register');
        $this->assertFalse($secondVerify['success']);
        $this->assertEquals('not_found', $secondVerify['reason']);
    }

    public function test_otp_cannot_be_verified_after_expiration(): void
    {
        $email = 'expired_otp@haomilk.test';
        $plainCode = EmailVerification::generate($email, 'register', 15);

        // Du hành thời gian qua thời hạn 15 phút
        Carbon::setTestNow(now()->addMinutes(16));

        $result = EmailVerification::verifyDetailed($email, $plainCode, 'register');
        $this->assertFalse($result['success']);
        $this->assertEquals('expired', $result['reason']);

        // Reset thời gian
        Carbon::setTestNow();
    }

    public function test_wrong_otp_increments_attempts_counter(): void
    {
        $email = 'attempts_test@haomilk.test';
        EmailVerification::generate($email, 'register');

        $result = EmailVerification::verifyDetailed($email, '000000', 'register');
        $this->assertFalse($result['success']);
        $this->assertEquals('invalid_code', $result['reason']);
        $this->assertEquals(1, $result['attempts']);
        $this->assertEquals(4, $result['remaining_attempts']);

        $record = EmailVerification::where('email', $email)->where('action', 'register')->first();
        $this->assertNotNull($record);
        $this->assertEquals(1, $record->attempts);
    }

    public function test_max_five_failed_attempts_invalidates_otp(): void
    {
        $email = 'brute_force@haomilk.test';
        $plainCode = EmailVerification::generate($email, 'register');

        // Nhập sai 4 lần
        for ($i = 1; $i <= 4; $i++) {
            $res = EmailVerification::verifyDetailed($email, '111111', 'register');
            $this->assertFalse($res['success']);
            $this->assertEquals('invalid_code', $res['reason']);
            $this->assertEquals($i, $res['attempts']);
        }

        // Lần thứ 5 nhập sai -> Phải bị khóa và hủy mã
        $fifthRes = EmailVerification::verifyDetailed($email, '111111', 'register');
        $this->assertFalse($fifthRes['success']);
        $this->assertEquals('max_attempts_exceeded', $fifthRes['reason']);
        $this->assertEquals(0, $fifthRes['remaining_attempts']);

        // Bản ghi OTP bị xóa để ngăn brute-force tiếp
        $this->assertDatabaseMissing('email_verifications', [
            'email' => $email,
            'action' => 'register',
        ]);

        // Kể cả sau đó nhập đúng cũng không thể xác thực vì OTP đã bị hủy
        $subsequentRes = EmailVerification::verifyDetailed($email, $plainCode, 'register');
        $this->assertFalse($subsequentRes['success']);
        $this->assertEquals('not_found', $subsequentRes['reason']);
    }

    public function test_resend_cooldown_prevents_rapid_spam(): void
    {
        $email = 'cooldown@haomilk.test';
        EmailVerification::generate($email, 'register');

        // Ngay lập tức thử gửi lại (chưa qua 60 giây)
        $this->assertFalse(EmailVerification::canResend($email, 'register'));
        $this->assertGreaterThan(0, EmailVerification::secondsUntilResend($email, 'register'));

        // Sau 61 giây
        Carbon::setTestNow(now()->addSeconds(61));
        $this->assertTrue(EmailVerification::canResend($email, 'register'));
        $this->assertEquals(0, EmailVerification::secondsUntilResend($email, 'register'));

        Carbon::setTestNow();
    }

    public function test_hourly_rate_limit_blocks_spamming(): void
    {
        $email = 'rate_limit@haomilk.test';

        // Hit 5 lần trong 1 giờ
        for ($i = 1; $i <= 5; $i++) {
            $this->assertFalse(EmailVerification::isRateLimited($email, 'register'));
            EmailVerification::hitRateLimiter($email, 'register');
        }

        // Lần thứ 6 phải bị chặn
        $this->assertTrue(EmailVerification::isRateLimited($email, 'register'));
    }

    public function test_ip_rate_limiting_blocks_mass_spam_across_multiple_emails(): void
    {
        $ip = '198.51.100.42';

        // Gửi tới 15 email khác nhau từ cùng 1 IP
        for ($i = 1; $i <= 15; $i++) {
            $email = "spam_target_{$i}@haomilk.test";
            $this->assertFalse(EmailVerification::isRateLimited($email, 'register', $ip));
            EmailVerification::hitRateLimiter($email, 'register', $ip);
        }

        // Lần thứ 16 dù là email hoàn toàn mới cũng bị chặn bởi IP limit
        $newEmail = 'brand_new_victim@haomilk.test';
        $this->assertTrue(EmailVerification::isRateLimited($newEmail, 'register', $ip));
    }

    public function test_registration_flow_handles_mail_failure_gracefully(): void
    {
        // Giả lập Mail::to()->send() quăng lỗi (như lỗi kết nối SMTP)
        Mail::shouldReceive('to')
            ->andThrow(new Exception('SMTP Connection Timeout'));

        $response = $this->post(route('auth.register.post'), [
            'name' => 'Lỗi Mail User',
            'email' => 'mail_fail@haomilk.test',
            'phone' => '0987654321',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Người dùng vẫn được chuyển hướng đến trang thông báo kèm flash warning thay vì ném lỗi HTTP 500
        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHas('warning');

        // Chưa nhập OTP -> Tài khoản TUYỆT ĐỐI CHƯA LƯU trong DB (chỉ lưu session tạm)
        $this->assertDatabaseMissing('users', ['email' => 'mail_fail@haomilk.test']);
        $this->assertEquals('mail_fail@haomilk.test', session('pending_registration.email'));
    }

    public function test_forgot_password_flow_with_hashed_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'forgot_pwd@haomilk.test',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->post(route('auth.forgot-password.send'), [
            'email' => 'forgot_pwd@haomilk.test',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('auth.reset-password', ['email' => 'forgot_pwd@haomilk.test']));

        Mail::assertSent(EmailVerificationMail::class, function ($mail) {
            return $mail->hasTo('forgot_pwd@haomilk.test')
                && $mail->action === 'reset_password'
                && strlen($mail->code) === 6;
        });

        // Lấy mã từ bản ghi DB và test verify qua flow
        $mail = Mail::sent(EmailVerificationMail::class)->first();
        $plainCode = $mail->code;

        // Xác thực và đổi mật khẩu thành công
        $resetResponse = $this->post(route('auth.reset-password.post'), [
            'email' => 'forgot_pwd@haomilk.test',
            'code' => $plainCode,
            'password' => 'NewPassword456!',
            'password_confirmation' => 'NewPassword456!',
        ]);

        $resetResponse->assertRedirect(route('auth.login'));
        $resetResponse->assertSessionHas('success');

        // Mật khẩu mới hoạt động
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword456!', $user->password));

        // OTP đã bị xóa sau khi sử dụng
        $this->assertDatabaseMissing('email_verifications', [
            'email' => 'forgot_pwd@haomilk.test',
            'action' => 'reset_password',
        ]);
    }
}
