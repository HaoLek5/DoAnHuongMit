<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationMail;
use App\Models\EmailVerification;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_account_email_is_haopak2k5_and_verified(): void
    {
        $this->seed(UserSeeder::class);

        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('haopak2k5@gmail.com', $admin->email);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue($admin->hasVerifiedEmail());
    }

    public function test_registration_does_not_save_user_until_otp_is_verified(): void
    {
        Mail::fake();

        $response = $this->post(route('auth.register.post'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'nguyenvana@example.com',
            'phone' => '0901234567',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('verification.notice'));

        // Chưa nhập mã OTP -> Tuyệt đối CHƯA LƯU vào bảng users và CHƯA ĐĂNG NHẬP
        $this->assertDatabaseMissing('users', ['email' => 'nguyenvana@example.com']);
        $this->assertFalse(auth()->check());

        Mail::assertSent(EmailVerificationMail::class, function ($mail) {
            return $mail->hasTo('nguyenvana@example.com')
                && $mail->action === 'register'
                && strlen($mail->code) === 6;
        });

        $this->assertDatabaseHas('email_verifications', [
            'email' => 'nguyenvana@example.com',
            'action' => 'register',
        ]);

        $mail = Mail::sent(EmailVerificationMail::class)->first();
        $code = $mail->code;

        // Thử nhập sai mã -> Vẫn không lưu user
        $wrongResponse = $this->post(route('verification.verify'), [
            'code' => '999999',
        ]);
        $wrongResponse->assertSessionHasErrors('code');
        $this->assertDatabaseMissing('users', ['email' => 'nguyenvana@example.com']);

        // Nhập đúng mã OTP -> Lúc này MỚI TẠO USER và TỰ ĐỘNG ĐĂNG NHẬP
        $verifyResponse = $this->post(route('verification.verify'), [
            'code' => $code,
        ]);

        $verifyResponse->assertRedirect(route('home'));

        $user = User::where('email', 'nguyenvana@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertTrue(auth()->check());
        $this->assertDatabaseMissing('email_verifications', [
            'email' => 'nguyenvana@example.com',
            'action' => 'register',
        ]);
    }

    public function test_registration_requires_phone_number(): void
    {
        Mail::fake();

        $response = $this->post(route('auth.register.post'), [
            'name' => 'Nguyễn Văn B',
            'email' => 'nguyenvanb@example.com',
            'phone' => '',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['phone']);
        $this->assertDatabaseMissing('users', ['email' => 'nguyenvanb@example.com']);
        Mail::assertNothingSent();
    }

    public function test_user_cannot_verify_with_wrong_code(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'test_unverified@example.com',
        ]);

        EmailVerification::generate('test_unverified@example.com', 'register');

        $response = $this->actingAs($user)->post(route('verification.verify'), [
            'code' => '999999', // Giả sử mã sai
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_user_can_verify_with_correct_otp(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'test_verify_success@example.com',
        ]);

        $code = EmailVerification::generate('test_verify_success@example.com', 'register');

        $response = $this->actingAs($user)->post(route('verification.verify'), [
            'code' => $code,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertDatabaseMissing('email_verifications', [
            'email' => 'test_verify_success@example.com',
            'action' => 'register',
        ]);
    }

    public function test_unverified_user_logging_in_is_redirected_to_verify_email(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'customer_unverified@example.com',
            'password' => Hash::make('Password123'),
        ]);

        $response = $this->post(route('auth.login.post'), [
            'email' => 'customer_unverified@example.com',
            'password' => 'Password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_user_can_request_otp_before_changing_password(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'change_pass_user@example.com',
        ]);

        $response = $this->actingAs($user)->postJson(route('account.password.send-code'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        Mail::assertSent(EmailVerificationMail::class, function ($mail) {
            return $mail->hasTo('change_pass_user@example.com')
                && $mail->action === 'change_password'
                && strlen($mail->code) === 6;
        });

        // Test cooldown rate limit
        $rateLimitResponse = $this->actingAs($user)->postJson(route('account.password.send-code'));
        $rateLimitResponse->assertStatus(429);
        $rateLimitResponse->assertJson([
            'success' => false,
        ]);
    }

    public function test_user_cannot_change_password_without_or_with_invalid_otp(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        // Thiếu OTP
        $response = $this->actingAs($user)->put(route('account.password.update'), [
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);
        $response->assertSessionHasErrors('verification_code');

        // Sai OTP
        EmailVerification::generate($user->email, 'change_password');
        $responseWrongOtp = $this->actingAs($user)->put(route('account.password.update'), [
            'verification_code' => '000000',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);
        $responseWrongOtp->assertSessionHasErrors('verification_code');
        $this->assertTrue(Hash::check('OldPassword123', $user->fresh()->password));
    }

    public function test_user_can_change_password_with_valid_otp(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        $code = EmailVerification::generate($user->email, 'change_password');

        $response = $this->actingAs($user)->put(route('account.password.update'), [
            'verification_code' => $code,
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ]);

        $response->assertSessionHas('password_success');
        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));

        // OTP bị xóa ngay sau khi đổi thành công (single use)
        $this->assertDatabaseMissing('email_verifications', [
            'email' => $user->email,
            'action' => 'change_password',
        ]);
    }

    public function test_user_can_request_forgot_password_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'forgot_user@example.com',
        ]);

        $response = $this->post(route('auth.forgot-password.send'), [
            'email' => 'forgot_user@example.com',
        ]);

        $response->assertRedirect(route('auth.reset-password', ['email' => 'forgot_user@example.com']));

        Mail::assertSent(EmailVerificationMail::class, function ($mail) {
            return $mail->hasTo('forgot_user@example.com')
                && $mail->action === 'reset_password'
                && strlen($mail->code) === 6;
        });

        $this->assertDatabaseHas('email_verifications', [
            'email' => 'forgot_user@example.com',
            'action' => 'reset_password',
        ]);
    }

    public function test_forgot_password_rejects_unregistered_email(): void
    {
        Mail::fake();

        $response = $this->post(route('auth.forgot-password.send'), [
            'email' => 'notfound@example.com',
        ]);

        $response->assertSessionHasErrors('email');
        Mail::assertNothingSent();
    }

    public function test_user_cannot_reset_password_with_invalid_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'reset_fail@example.com',
            'password' => Hash::make('OldPassword123'),
        ]);

        EmailVerification::generate('reset_fail@example.com', 'reset_password');

        $response = $this->post(route('auth.reset-password.post'), [
            'email' => 'reset_fail@example.com',
            'code' => '000000',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertTrue(Hash::check('OldPassword123', $user->fresh()->password));
    }

    public function test_user_can_reset_password_with_valid_otp_and_unlock(): void
    {
        $user = User::factory()->create([
            'email' => 'reset_success@example.com',
            'password' => Hash::make('OldPassword123'),
            'login_attempts' => 5,
            'locked_until' => now()->addMinutes(15),
            'email_verified_at' => null,
        ]);

        $code = EmailVerification::generate('reset_success@example.com', 'reset_password');

        $response = $this->post(route('auth.reset-password.post'), [
            'email' => 'reset_success@example.com',
            'code' => $code,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $response->assertRedirect(route('auth.login'));
        $response->assertSessionHas('success');

        $userFresh = $user->fresh();
        $this->assertTrue(Hash::check('NewPassword123', $userFresh->password));
        $this->assertFalse($userFresh->isLocked());
        $this->assertEquals(0, $userFresh->login_attempts);
        $this->assertNotNull($userFresh->email_verified_at);

        $this->assertDatabaseMissing('email_verifications', [
            'email' => 'reset_success@example.com',
            'action' => 'reset_password',
        ]);
    }

    public function test_user_can_update_profile_without_changing_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'phone' => '0901111111',
        ]);

        $response = $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'Updated Name',
            'phone' => '0902222222',
            'email' => 'hacker_attempt@example.com',
        ]);

        $response->assertSessionHas('account_success');
        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('0902222222', $user->phone);
        $this->assertEquals('original@example.com', $user->email);
    }
}
