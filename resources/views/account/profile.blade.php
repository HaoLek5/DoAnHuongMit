@extends('account.layout')

@section('title', 'Thông tin tài khoản - Hương Mít')
@section('page_name', 'Thông tin tài khoản')

@section('account_content')
<div class="space-y-6">

    <!-- ── CARD 1: THÔNG TIN CÁ NHÂN ── -->
    <div class="bg-white rounded-3xl border border-primary-100/80 p-6 sm:p-8 shadow-xs">
        
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-800">Thông tin cá nhân</h2>
                <p class="text-xs text-slate-400 mt-0.5">Cập nhật thông tin liên hệ và hồ sơ của bạn trên Hương Mít</p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-primary-50 text-primary flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
        </div>

        @if(session('account_success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('account_success') }}</span>
            </div>
        @endif

        <form action="{{ route('account.profile.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                
                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Họ và tên <span class="text-primary">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                               class="w-full pl-10 pr-4 py-3 rounded-2xl border {{ $errors->has('name') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-white' }} text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                    </div>
                    @error('name')
                        <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Số điện thoại
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="0987654321"
                               class="w-full pl-10 pr-4 py-3 rounded-2xl border {{ $errors->has('phone') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-white' }} text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                    </div>
                    @error('phone')
                        <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div class="sm:col-span-2">
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Địa chỉ Email <span class="text-primary">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="w-full pl-10 pr-4 py-3 rounded-2xl border {{ $errors->has('email') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-white' }} text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                    </div>
                    @error('email')
                        <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" 
                        class="px-6 py-3 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-xs shadow-md shadow-primary/25 hover:shadow-lg transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Lưu thay đổi</span>
                </button>
            </div>
        </form>

    </div>

    <!-- ── CARD 2: ĐỔI MẬT KHẨU ── -->
    <div class="bg-white rounded-3xl border border-primary-100/80 p-6 sm:p-8 shadow-xs">
        
        <div class="flex items-center justify-between pb-6 mb-6 border-b border-slate-100">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-800">Đổi mật khẩu</h2>
                <p class="text-xs text-slate-400 mt-0.5">Nên sử dụng mật khẩu mạnh kết hợp chữ, số và ký tự đặc biệt</p>
            </div>
            <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
        </div>

        @if(session('password_success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('password_success') }}</span>
            </div>
        @endif

        <form action="{{ route('account.password.update') }}" method="POST" class="space-y-5"
              x-data="{
                  showPass: false,
                  countdown: {{ $passwordCodeSeconds ?? 0 }},
                  sendingCode: false,
                  codeMessage: '',
                  codeError: '',
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
                  async sendCode() {
                      if (this.countdown > 0 || this.sendingCode) return;
                      this.sendingCode = true;
                      this.codeMessage = '';
                      this.codeError = '';
                      try {
                          const res = await fetch('{{ route('account.password.send-code') }}', {
                              method: 'POST',
                              headers: {
                                  'Content-Type': 'application/json',
                                  'Accept': 'application/json',
                                  'X-CSRF-TOKEN': '{{ csrf_token() }}'
                              }
                          });
                          const data = await res.json();
                          if (res.ok && data.success) {
                              this.codeMessage = data.message;
                              this.countdown = data.seconds || 60;
                              this.startTimer();
                          } else {
                              this.codeError = data.message || 'Không thể gửi mã xác thực. Vui lòng thử lại sau.';
                              if (data.seconds) {
                                  this.countdown = data.seconds;
                                  this.startTimer();
                              }
                          }
                      } catch (e) {
                          this.codeError = 'Đã có lỗi xảy ra kết nối mạng. Vui lòng thử lại.';
                      } finally {
                          this.sendingCode = false;
                      }
                  }
              }">
            @csrf
            @method('PUT')

            <!-- Current Password -->
            <div>
                <label for="current_password" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Mật khẩu hiện tại <span class="text-primary">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input :type="showPass ? 'text' : 'password'" id="current_password" name="current_password" required
                           class="w-full pl-10 pr-10 py-3 rounded-2xl border {{ $errors->has('current_password') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-white' }} text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                </div>
                @error('current_password')
                    <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Verification OTP -->
            <div>
                <label for="verification_code" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Mã xác thực email (OTP) <span class="text-primary">*</span>
                </label>
                <div class="flex flex-col sm:flex-row gap-2.5">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input type="text"
                               id="verification_code"
                               name="verification_code"
                               inputmode="numeric"
                               autocomplete="one-time-code"
                               maxlength="6"
                               placeholder="Nhập mã 6 chữ số"
                               required
                               class="w-full pl-10 pr-4 py-3 rounded-2xl border {{ $errors->has('verification_code') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-white' }} text-xs font-semibold tracking-wider text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                    </div>
                    <button type="button"
                            @click="sendCode()"
                            :disabled="countdown > 0 || sendingCode"
                            class="px-5 py-3 rounded-2xl font-bold text-xs flex items-center justify-center gap-1.5 transition-all whitespace-nowrap"
                            :class="countdown > 0 || sendingCode ? 'bg-slate-100 text-slate-400 cursor-not-allowed border border-slate-200' : 'bg-primary-50 hover:bg-primary-100 text-primary border border-primary-200/80 active:scale-95'">
                        <svg x-show="sendingCode" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-primary" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg x-show="!sendingCode" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <span x-show="countdown <= 0 && !sendingCode">Lấy mã xác thực</span>
                        <span x-show="sendingCode" x-cloak>Đang gửi mã...</span>
                        <span x-show="countdown > 0 && !sendingCode" x-cloak>Gửi lại sau (<span x-text="countdown"></span>s)</span>
                    </button>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Mã xác thực 6 số sẽ được gửi đến email <strong class="text-slate-600">{{ $user->email }}</strong> (hiệu lực 15 phút).</p>

                <template x-if="codeMessage">
                    <p class="text-[11px] text-emerald-600 font-semibold mt-1" x-text="codeMessage"></p>
                </template>
                <template x-if="codeError">
                    <p class="text-[11px] text-rose-500 font-semibold mt-1" x-text="codeError"></p>
                </template>
                @error('verification_code')
                    <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                
                <!-- New Password -->
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Mật khẩu mới (tối thiểu 6 ký tự) <span class="text-primary">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <input :type="showPass ? 'text' : 'password'" id="password" name="password" required
                               class="w-full pl-10 pr-10 py-3 rounded-2xl border {{ $errors->has('password') ? 'border-rose-400 bg-rose-50/30' : 'border-slate-200 bg-white' }} text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                    </div>
                    @error('password')
                        <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Confirmation -->
                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Xác nhận lại mật khẩu mới <span class="text-primary">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <input :type="showPass ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                               class="w-full pl-10 pr-10 py-3 rounded-2xl border border-slate-200 bg-white text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                    </div>
                </div>

            </div>

            <!-- Show Password Toggle Checkbox -->
            <div class="flex items-center gap-2">
                <input type="checkbox" id="toggle_password" @change="showPass = !showPass" class="rounded border-slate-300 text-primary focus:ring-primary/20">
                <label for="toggle_password" class="text-xs text-slate-500 select-none cursor-pointer">Hiển thị mật khẩu</label>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" 
                        class="px-6 py-3 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Cập nhật mật khẩu</span>
                </button>
            </div>

        </form>

    </div>

</div>
@endsection
