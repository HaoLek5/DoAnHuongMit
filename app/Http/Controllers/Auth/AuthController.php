<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationMail;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // ─────────────────────────────────────────
    //  REGISTER
    // ─────────────────────────────────────────

    public function showRegister()
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            return $user->hasVerifiedEmail()
                ? redirect()->route('home')
                : redirect()->route('verification.notice');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\+\-\s\(\)]+$/'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'name.required' => 'Vui lòng nhập họ tên.',
            'name.max' => 'Họ tên không được vượt quá 100 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
        ]);

        $normalizedEmail = strtolower(trim($validated['email']));

        // Kiểm tra xem email đã có tài khoản kích hoạt trước đó hay chưa
        $existingUser = User::where('email', $normalizedEmail)->first();
        if ($existingUser) {
            if ($existingUser->hasVerifiedEmail()) {
                return back()
                    ->withInput($request->only('name', 'email', 'phone'))
                    ->withErrors(['email' => 'Địa chỉ email này đã được đăng ký và kích hoạt. Vui lòng đăng nhập.']);
            }

            // Nếu tài khoản cũ chưa kích hoạt từ trước, dọn dẹp để cho phép đăng ký mới
            $existingUser->delete();
        }

        // Lưu thông tin đăng ký tạm thời vào session (TUYỆT ĐỐI CHƯA LƯU VÀO DATABASE KHI CHƯA NHẬP OTP)
        session([
            'pending_registration' => [
                'name' => $validated['name'],
                'email' => $normalizedEmail,
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
            ],
        ]);

        // Tạo mã OTP 6 số hash bảo mật và gửi qua email
        $code = EmailVerification::generate($normalizedEmail, 'register', 15, $request->ip());
        EmailVerification::hitRateLimiter($normalizedEmail, 'register', $request->ip());

        $mailSent = true;
        try {
            Mail::to($normalizedEmail)->send(new EmailVerificationMail($code, 'register', $validated['name']));
        } catch (\Throwable $e) {
            $mailSent = false;
            Log::error('Lỗi gửi email xác thực đăng ký cho '.$normalizedEmail.': '.$e->getMessage());
        }

        if (! $mailSent) {
            return redirect()->route('verification.notice')
                ->with('warning', 'Hệ thống chưa thể gửi email OTP do sự cố kết nối máy chủ thư. Vui lòng bấm "Gửi lại mã" sau ít phút.');
        }

        return redirect()->route('verification.notice')
            ->with('success', 'Mã xác thực OTP 6 số đã được gửi tới email của bạn. Vui lòng nhập mã để hoàn tất đăng ký.');
    }

    // ─────────────────────────────────────────
    //  EMAIL VERIFICATION (OTP)
    // ─────────────────────────────────────────

    public function showVerifyEmail()
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            if ($user->hasVerifiedEmail()) {
                return redirect()->route('home');
            }

            $email = $user->email;
            $userName = $user->name;
        } else {
            $pending = session('pending_registration');
            if (! $pending || empty($pending['email'])) {
                return redirect()->route('auth.register')
                    ->withErrors(['email' => 'Vui lòng điền thông tin đăng ký trước khi xác thực mã OTP.']);
            }

            $email = $pending['email'];
            $userName = $pending['name'];
        }

        $secondsUntilResend = EmailVerification::secondsUntilResend($email, 'register');

        return view('auth.verify_email', compact('email', 'userName', 'secondsUntilResend'));
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'Vui lòng nhập mã xác thực OTP.',
            'code.size' => 'Mã xác thực gồm đúng 6 chữ số.',
        ]);

        // 1. Trường hợp người dùng đã đăng nhập (tài khoản cũ chưa kích hoạt)
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            if ($user->hasVerifiedEmail()) {
                return redirect()->route('home');
            }

            $result = EmailVerification::verifyDetailed($user->email, $request->code, 'register');
            if (! $result['success']) {
                return back()->withErrors(['code' => $result['message']]);
            }

            $user->markEmailAsVerified();

            return redirect()->route('home')
                ->with('success', 'Xác thực tài khoản thành công! Chào mừng bạn đến với Hương Mít.');
        }

        // 2. Trường hợp đăng ký mới: Chỉ lưu vào database khi mã OTP được nhập ĐÚNG
        $pending = session('pending_registration');
        if (! $pending || empty($pending['email'])) {
            return redirect()->route('auth.register')
                ->withErrors(['email' => 'Phiên đăng ký đã hết hạn hoặc không tồn tại. Vui lòng đăng ký lại.']);
        }

        $email = $pending['email'];
        $result = EmailVerification::verifyDetailed($email, $request->code, 'register');

        if (! $result['success']) {
            return back()->withErrors(['code' => $result['message']]);
        }

        // Tránh race-condition nếu email đã kịp đăng ký ở luồng khác
        if (User::where('email', $email)->exists()) {
            session()->forget('pending_registration');

            return redirect()->route('auth.login')
                ->withErrors(['email' => 'Tài khoản này đã tồn tại trong hệ thống. Vui lòng đăng nhập.']);
        }

        // Tạo bản ghi User chính thức vào Database (ĐÃ XÁC THỰC EMAIL)
        $user = User::create([
            'name' => $pending['name'],
            'email' => $email,
            'phone' => $pending['phone'] ?? null,
            'password' => $pending['password'],
            'role' => 'customer',
        ]);

        $user->markEmailAsVerified();

        session()->forget('pending_registration');

        // Tự động đăng nhập cho người dùng
        Auth::login($user, remember: true);

        return redirect()->route('home')
            ->with('success', 'Đăng ký và kích hoạt tài khoản thành công! Chào mừng bạn đến với Hương Mít.');
    }

    public function resendVerificationEmail(Request $request)
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            if ($user->hasVerifiedEmail()) {
                return redirect()->route('home');
            }

            $email = $user->email;
            $userName = $user->name;
        } else {
            $pending = session('pending_registration');
            if (! $pending || empty($pending['email'])) {
                return redirect()->route('auth.register')
                    ->withErrors(['email' => 'Vui lòng điền thông tin đăng ký trước khi yêu cầu gửi mã.']);
            }

            $email = $pending['email'];
            $userName = $pending['name'];
        }

        if (EmailVerification::isRateLimited($email, 'register', $request->ip())) {
            return back()->withErrors([
                'resend' => 'Bạn đã yêu cầu gửi mã quá nhiều lần trong 1 giờ. Vui lòng thử lại sau.',
            ]);
        }

        if (! EmailVerification::canResend($email, 'register')) {
            $seconds = EmailVerification::secondsUntilResend($email, 'register');

            return back()->withErrors([
                'resend' => "Vui lòng đợi {$seconds} giây trước khi yêu cầu gửi lại mã.",
            ]);
        }

        $code = EmailVerification::generate($email, 'register', 15, $request->ip());
        EmailVerification::hitRateLimiter($email, 'register', $request->ip());

        try {
            Mail::to($email)->send(new EmailVerificationMail($code, 'register', $userName));
        } catch (\Throwable $e) {
            Log::error('Lỗi gửi lại email xác thực cho '.$email.': '.$e->getMessage());

            return back()->withErrors([
                'resend' => 'Không thể gửi email lúc này do sự cố kết nối máy chủ gửi thư. Vui lòng thử lại sau ít phút.',
            ]);
        }

        return back()->with('success', 'Mã xác thực mới đã được gửi tới email của bạn. Vui lòng kiểm tra hộp thư đến (hoặc thư rác).');
    }

    // ─────────────────────────────────────────
    //  LOGIN
    // ─────────────────────────────────────────

    public function showLogin()
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            return $user->hasVerifiedEmail()
                ? redirect()->route('home')
                : redirect()->route('verification.notice');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        // Find user first (to check account lock)
        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Check if account is locked
            if ($user->isLocked()) {
                $minutesLeft = (int) max(1, ceil(now()->diffInMinutes($user->locked_until, false)));

                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => "Tài khoản bị tạm khóa do đăng nhập sai quá nhiều lần. Vui lòng thử lại sau {$minutesLeft} phút."]);
            }
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            /** @var User $authUser */
            $authUser = Auth::user();
            $authUser->resetLoginAttempts();

            if ($authUser->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            // Nếu tài khoản khách hàng chưa xác thực email, yêu cầu xác thực OTP
            if (! $authUser->hasVerifiedEmail()) {
                if (EmailVerification::canResend($authUser->email, 'register') && ! EmailVerification::isRateLimited($authUser->email, 'register', $request->ip())) {
                    $code = EmailVerification::generate($authUser->email, 'register', 15, $request->ip());
                    EmailVerification::hitRateLimiter($authUser->email, 'register', $request->ip());
                    try {
                        Mail::to($authUser->email)->send(new EmailVerificationMail($code, 'register', $authUser->name));
                    } catch (\Throwable $e) {
                        Log::error('Lỗi gửi email xác thực đăng nhập cho '.$authUser->email.': '.$e->getMessage());
                    }
                }

                return redirect()->route('verification.notice')
                    ->with('info', 'Tài khoản chưa được kích hoạt. Vui lòng nhập mã xác thực OTP đã được gửi tới email của bạn.');
            }

            return redirect()->intended(route('home'));
        }

        // Failed attempt
        if ($user) {
            $user->incrementLoginAttempts();
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Email hoặc mật khẩu không đúng.']);
    }

    // ─────────────────────────────────────────
    //  FORGOT / RESET PASSWORD (WHEN NOT LOGGED IN)
    // ─────────────────────────────────────────

    public function showForgotPassword(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        $email = $request->query('email', session('reset_password_email', ''));

        return view('auth.forgot_password', compact('email'));
    }

    public function sendResetOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email của bạn.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Địa chỉ email này chưa được đăng ký trong hệ thống Hương Mít.']);
        }

        if (EmailVerification::isRateLimited($user->email, 'reset_password', $request->ip())) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Bạn đã yêu cầu gửi mã quá nhiều lần trong 1 giờ. Vui lòng thử lại sau.']);
        }

        if (! EmailVerification::canResend($user->email, 'reset_password')) {
            $seconds = EmailVerification::secondsUntilResend($user->email, 'reset_password');

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => "Mã xác thực vừa được gửi. Vui lòng đợi {$seconds} giây trước khi yêu cầu mã mới."]);
        }

        $code = EmailVerification::generate($user->email, 'reset_password', 15, $request->ip());
        EmailVerification::hitRateLimiter($user->email, 'reset_password', $request->ip());

        try {
            Mail::to($user->email)->send(new EmailVerificationMail($code, 'reset_password', $user->name));
        } catch (\Throwable $e) {
            Log::error('Lỗi gửi email đặt lại mật khẩu cho '.$user->email.': '.$e->getMessage());

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Hệ thống gửi thư đang gặp sự cố kết nối. Vui lòng thử lại sau ít phút.']);
        }

        session(['reset_password_email' => $user->email]);

        return redirect()->route('auth.reset-password', ['email' => $user->email])
            ->with('success', "Mã xác thực 6 số đã được gửi tới email {$user->email}. Vui lòng kiểm tra hộp thư và thiết lập mật khẩu mới.");
    }

    public function resendResetOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Vui lòng cung cấp email.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            $msg = 'Email không tồn tại trong hệ thống.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 404);
            }

            return back()->withErrors(['email' => $msg]);
        }

        if (EmailVerification::isRateLimited($user->email, 'reset_password', $request->ip())) {
            $msg = 'Bạn đã yêu cầu gửi mã quá nhiều lần trong 1 giờ. Vui lòng thử lại sau.';
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 429);
            }

            return back()->withErrors(['email' => $msg]);
        }

        if (! EmailVerification::canResend($user->email, 'reset_password')) {
            $seconds = EmailVerification::secondsUntilResend($user->email, 'reset_password');
            $msg = "Vui lòng đợi {$seconds} giây trước khi yêu cầu gửi lại mã.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'seconds' => $seconds,
                ], 429);
            }

            return back()->withErrors(['resend' => $msg]);
        }

        $code = EmailVerification::generate($user->email, 'reset_password', 15, $request->ip());
        EmailVerification::hitRateLimiter($user->email, 'reset_password', $request->ip());

        try {
            Mail::to($user->email)->send(new EmailVerificationMail($code, 'reset_password', $user->name));
        } catch (\Throwable $e) {
            Log::error('Lỗi gửi lại email đặt lại mật khẩu cho '.$user->email.': '.$e->getMessage());
            $msg = 'Hệ thống gửi thư đang gặp sự cố kết nối. Vui lòng thử lại sau ít phút.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                ], 500);
            }

            return back()->withErrors(['resend' => $msg]);
        }

        $msg = "Mã xác thực mới đã được gửi tới email {$user->email}.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'seconds' => 60,
            ]);
        }

        return back()->with('success', $msg);
    }

    public function showResetPassword(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        $email = $request->query('email', session('reset_password_email', ''));
        $secondsUntilResend = $email ? EmailVerification::secondsUntilResend($email, 'reset_password') : 0;

        return view('auth.reset_password', compact('email', 'secondsUntilResend'));
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Địa chỉ email không hợp lệ.',
            'email.exists' => 'Địa chỉ email này chưa được đăng ký trong hệ thống.',
            'code.required' => 'Vui lòng nhập mã xác thực OTP.',
            'code.size' => 'Mã xác thực gồm đúng 6 chữ số.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự, bao gồm cả chữ và số.',
        ]);

        $result = EmailVerification::verifyDetailed($validated['email'], $validated['code'], 'reset_password');

        if (! $result['success']) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'code'))
                ->withErrors(['code' => $result['message']]);
        }

        $user = User::where('email', $validated['email'])->first();

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Nếu email tài khoản này trước đây chưa được xác thực, đánh dấu là đã xác thực luôn
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // Mở khóa nếu tài khoản đang bị tạm khóa đăng nhập sai
        $user->resetLoginAttempts();

        session()->forget('reset_password_email');

        return redirect()->route('auth.login')
            ->with('success', 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập ngay với mật khẩu mới.');
    }

    // ─────────────────────────────────────────
    //  LOGOUT
    // ─────────────────────────────────────────

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
