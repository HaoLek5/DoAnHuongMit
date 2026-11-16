@extends('layouts.auth')

@section('title', 'Đặt lại mật khẩu')
@section('meta_description', 'Tạo mật khẩu mới cho tài khoản Hương Mít của bạn.')

@section('content')

    {{-- Icon & Heading --}}
    <div class="mb-6 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-pink-50 text-primary mb-3.5 shadow-sm border border-pink-100">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Đặt lại mật khẩu mới</h1>
        <p class="text-xs sm:text-sm text-gray-500 max-w-sm mx-auto leading-relaxed">
            Nhập mã xác thực OTP 6 số đã được gửi tới email của bạn và thiết lập mật khẩu đăng nhập mới.
        </p>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
    <div class="flex items-start gap-3 p-4 mb-5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800">
        <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    {{-- Global Error Alert --}}
    @if($errors->any())
    <div class="flex items-start gap-3 p-4 mb-5 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800">
        <svg class="w-4 h-4 text-rose-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div class="space-y-1">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Reset Password Form --}}
    <form method="POST" action="{{ route('auth.reset-password.post') }}" class="space-y-4"
          x-data="{
              showPass: false,
              email: '{{ old('email', $email) }}',
              countdown: {{ $secondsUntilResend ?? 0 }},
              sendingCode: false,
              resendMessage: '',
              resendError: '',
              timer: null,
              init() {
                  if (this.countdown > 0) {
                      this.startTimer();
                  }
              },
              startTimer() {
                  if (this.timer) clearInterval(this.timer);
                  this.timer = setInterval(() => {
                      this.countdown--;
                      if (this.countdown <= 0) {
                          clearInterval(this.timer);
                          this.timer = null;
                      }
                  }, 1000);
              },
              async resendOtp() {
                  if (!this.email || this.countdown > 0 || this.sendingCode) return;
                  this.sendingCode = true;
                  this.resendMessage = '';
                  this.resendError = '';
                  try {
                      const res = await fetch('{{ route('auth.forgot-password.resend') }}', {
                          method: 'POST',
                          headers: {
                              'Content-Type': 'application/json',
                              'Accept': 'application/json',
                              'X-CSRF-TOKEN': '{{ csrf_token() }}'
                          },
                          body: JSON.stringify({ email: this.email })
                      });
                      const data = await res.json();
                      if (res.ok && data.success) {
                          this.resendMessage = data.message;
                          this.countdown = data.seconds || 60;
                          this.startTimer();
                      } else {
                          this.resendError = data.message || 'Không thể gửi lại mã. Vui lòng thử lại sau.';
                          if (data.seconds) {
                              this.countdown = data.seconds;
                              this.startTimer();
                          }
                      }
                  } catch (e) {
                      this.resendError = 'Lỗi kết nối. Vui lòng thử lại.';
                  } finally {
                      this.sendingCode = false;
                  }
              }
          }">
        @csrf

        {{-- Email --}}
        <div>
            <label for="reset-email" class="block text-xs font-bold text-gray-700 mb-1.5">
                Địa chỉ Email <span class="text-primary">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                    </svg>
                </div>
                <input
                    id="reset-email"
                    type="email"
                    name="email"
                    x-model="email"
                    required
                    placeholder="example@email.com"
                    class="w-full pl-10 pr-4 py-3 rounded-2xl border {{ $errors->has('email') ? 'border-rose-400 bg-rose-50/40 text-rose-900' : 'border-gray-200 bg-gray-50/50 text-gray-800 focus:bg-white focus:border-primary' }} text-xs font-semibold outline-none transition-all"
                >
            </div>
            @error('email')
                <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- OTP Code & Resend Button --}}
        <div>
            <label for="reset-code" class="block text-xs font-bold text-gray-700 mb-1.5">
                Mã xác thực OTP (6 số) <span class="text-primary">*</span>
            </label>
            <div class="flex flex-col sm:flex-row gap-2.5">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <input
                        id="reset-code"
                        type="text"
                        name="code"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        required
                        placeholder="Nhập 6 số"
                        class="w-full pl-10 pr-4 py-3 rounded-2xl border {{ $errors->has('code') ? 'border-rose-400 bg-rose-50/40 text-rose-900' : 'border-gray-200 bg-gray-50/50 text-gray-800 focus:bg-white focus:border-primary' }} text-xs font-semibold tracking-wider outline-none transition-all"
                    >
                </div>
                <button
                    type="button"
                    @click="resendOtp()"
                    :disabled="countdown > 0 || sendingCode || !email"
                    class="px-4 py-3 rounded-2xl font-bold text-xs flex items-center justify-center gap-1.5 transition-all whitespace-nowrap"
                    :class="countdown > 0 || sendingCode || !email ? 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200' : 'bg-pink-50 hover:bg-pink-100 text-primary border border-pink-200 active:scale-95'"
                >
                    <svg x-show="sendingCode" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-primary" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-show="countdown <= 0 && !sendingCode">Gửi lại mã</span>
                    <span x-show="sendingCode" x-cloak>Đang gửi...</span>
                    <span x-show="countdown > 0 && !sendingCode" x-cloak>Gửi lại (<span x-text="countdown"></span>s)</span>
                </button>
            </div>
            <template x-if="resendMessage">
                <p class="text-[11px] text-emerald-600 font-semibold mt-1" x-text="resendMessage"></p>
            </template>
            <template x-if="resendError">
                <p class="text-[11px] text-rose-500 font-semibold mt-1" x-text="resendError"></p>
            </template>
            @error('code')
                <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- New Password --}}
        <div>
            <label for="new-password" class="block text-xs font-bold text-gray-700 mb-1.5">
                Mật khẩu mới (tối thiểu 8 ký tự, có chữ và số) <span class="text-primary">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <input
                    id="new-password"
                    :type="showPass ? 'text' : 'password'"
                    name="password"
                    required
                    placeholder="••••••••"
                    class="w-full pl-10 pr-4 py-3 rounded-2xl border {{ $errors->has('password') ? 'border-rose-400 bg-rose-50/40 text-rose-900' : 'border-gray-200 bg-gray-50/50 text-gray-800 focus:bg-white focus:border-primary' }} text-xs font-semibold outline-none transition-all"
                >
            </div>
            @error('password')
                <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="new-password-confirmation" class="block text-xs font-bold text-gray-700 mb-1.5">
                Xác nhận lại mật khẩu mới <span class="text-primary">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <input
                    id="new-password-confirmation"
                    :type="showPass ? 'text' : 'password'"
                    name="password_confirmation"
                    required
                    placeholder="••••••••"
                    class="w-full pl-10 pr-4 py-3 rounded-2xl border border-gray-200 bg-gray-50/50 text-gray-800 focus:bg-white focus:border-primary text-xs font-semibold outline-none transition-all"
                >
            </div>
        </div>

        {{-- Toggle Show Password --}}
        <div class="flex items-center gap-2 pt-1">
            <input type="checkbox" id="toggle_password_reset" @change="showPass = !showPass" class="rounded border-gray-300 text-primary focus:ring-primary/20 cursor-pointer">
            <label for="toggle_password_reset" class="text-xs text-gray-500 select-none cursor-pointer">Hiển thị mật khẩu</label>
        </div>

        {{-- Submit Button --}}
        <div class="pt-2">
            <button
                type="submit"
                class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-xs shadow-md shadow-pink-500/20 hover:shadow-lg transition-all flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Cập nhật mật khẩu mới</span>
            </button>
        </div>
    </form>

    {{-- Back to login --}}
    <div class="mt-6 pt-5 border-t border-gray-100 text-center">
        <a href="{{ route('auth.login') }}" class="text-xs font-semibold text-gray-500 hover:text-primary transition-colors">
            ← Quay lại trang đăng nhập
        </a>
    </div>

@endsection

