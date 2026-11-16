@extends('account.layout')

@section('title', 'Thông tin tài khoản - Hương Mít')
@section('page_name', 'Thông tin tài khoản')

@section('account_content')
<div class="space-y-6"
     x-data="{
         passwordModalOpen: {{ ($errors->has('verification_code') || $errors->has('password') || session('password_code_sent')) ? 'true' : 'false' }},
         showPass: false,
         countdown: {{ $passwordCodeSeconds ?? 0 }},
         sendingCode: false,
         codeMessage: '{{ session('password_code_sent') ? addslashes(session('password_code_sent')) : '' }}',
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
                 this.codeError = 'Lỗi kết nối. Vui lòng thử lại.';
             } finally {
                 this.sendingCode = false;
             }
         }
     }">

    <!-- ── CARD: THÔNG TIN CÁ NHÂN ── -->
    <div class="bg-white rounded-3xl border border-primary-100/80 p-5 sm:p-6 shadow-xs">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-800">Thông tin cá nhân</h2>
            <button type="button" @click="passwordModalOpen = true"
                    class="px-3.5 py-1.5 rounded-xl border border-primary-200 bg-primary-50/50 hover:bg-primary-100/60 text-primary font-bold text-xs transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>Đổi mật khẩu</span>
            </button>
        </div>

        @if(session('account_success'))
            <div class="mb-5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('account_success') }}</span>
            </div>
        @endif

        @if(session('password_success'))
            <div class="mb-5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('password_success') }}</span>
            </div>
        @endif

        <form action="{{ route('account.profile.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Họ và tên <span class="text-primary">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-4 py-2.5 sm:py-3 rounded-2xl border {{ $errors->has('name') ? 'border-rose-400 bg-rose-50/30 text-rose-900' : 'border-slate-200 bg-white text-slate-800' }} text-xs font-semibold focus:border-primary outline-none transition-all">
                    @error('name')
                        <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 mb-1.5">
                        Số điện thoại
                    </label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="0987654321"
                           class="w-full px-4 py-2.5 sm:py-3 rounded-2xl border {{ $errors->has('phone') ? 'border-rose-400 bg-rose-50/30 text-rose-900' : 'border-slate-200 bg-white text-slate-800' }} text-xs font-semibold focus:border-primary outline-none transition-all">
                    @error('phone')
                        <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email (Vô hiệu hóa chỉnh sửa) -->
                <div class="sm:col-span-2">
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                        <span>Địa chỉ Email</span>
                        <span class="text-[11px] font-normal text-slate-400">Không thể chỉnh sửa</span>
                    </label>
                    <input type="email" id="email" value="{{ $user->email }}" readonly disabled
                           class="w-full px-4 py-2.5 sm:py-3 rounded-2xl border border-slate-200 bg-slate-50 text-slate-500 text-xs font-semibold outline-none cursor-not-allowed select-none">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-between gap-3">
                <button type="button" @click="passwordModalOpen = true"
                        class="px-5 py-2.5 sm:py-3 rounded-2xl border border-slate-200 hover:border-primary-300 hover:bg-primary-50/40 text-slate-700 hover:text-primary font-bold text-xs transition-all flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Đổi mật khẩu</span>
                </button>

                <button type="submit" 
                        class="px-6 py-2.5 sm:py-3 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-xs shadow-md shadow-primary/20 hover:shadow-lg transition-all">
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>

    <!-- ── THẺ POPUP: ĐỔI MẬT KHẨU ── -->
    <div x-show="passwordModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="password-modal-title" role="dialog" aria-modal="true">
        
        <!-- Backdrop -->
        <div x-show="passwordModalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="passwordModalOpen = false"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="passwordModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-lg border border-primary-100/80">

                <!-- Header Popup -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h3 id="password-modal-title" class="font-bold text-base text-slate-800">Đổi mật khẩu</h3>
                        <p class="text-[11px] text-slate-500">Mã xác thực OTP 6 số sẽ được gửi về email của bạn</p>
                    </div>
                    <button type="button" @click="passwordModalOpen = false"
                            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Form Đổi Mật Khẩu -->
                <form action="{{ route('account.password.update') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')

                    {{-- Email (Chỉ đọc) --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Địa chỉ Email
                        </label>
                        <input type="email" value="{{ $user->email }}" readonly disabled
                               class="w-full px-4 py-2.5 sm:py-3 rounded-2xl border border-slate-200 bg-slate-50 text-slate-500 text-xs font-semibold outline-none cursor-not-allowed select-none">
                    </div>

                    {{-- Mã OTP & Nút gửi mã --}}
                    <div>
                        <label for="verification_code" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Mã xác thực OTP (6 số) <span class="text-primary">*</span>
                        </label>
                        <div class="flex flex-col sm:flex-row gap-2.5">
                            <input type="text"
                                   id="verification_code"
                                   name="verification_code"
                                   inputmode="numeric"
                                   autocomplete="one-time-code"
                                   maxlength="6"
                                   placeholder="Nhập 6 số"
                                   required
                                   class="flex-1 px-4 py-2.5 sm:py-3 rounded-2xl border {{ $errors->has('verification_code') ? 'border-rose-400 bg-rose-50/30 text-rose-900' : 'border-slate-200 bg-white text-slate-800' }} text-xs font-semibold tracking-wider focus:border-primary outline-none transition-all">
                            <button type="button"
                                    @click="sendCode()"
                                    :disabled="countdown > 0 || sendingCode"
                                    class="px-5 py-2.5 sm:py-3 rounded-2xl font-bold text-xs flex items-center justify-center gap-1.5 transition-all whitespace-nowrap"
                                    :class="countdown > 0 || sendingCode ? 'bg-slate-100 text-slate-400 cursor-not-allowed border border-slate-200' : 'bg-primary-50 hover:bg-primary-100 text-primary border border-primary-200 active:scale-95'">
                                <span x-show="countdown <= 0 && !sendingCode">Gửi mã xác thực</span>
                                <span x-show="sendingCode" x-cloak>Đang gửi...</span>
                                <span x-show="countdown > 0 && !sendingCode" x-cloak>Gửi lại (<span x-text="countdown"></span>s)</span>
                            </button>
                        </div>
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

                    {{-- Mật khẩu mới --}}
                    <div>
                        <label for="new_password" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Mật khẩu mới (tối thiểu 8 ký tự, có chữ và số) <span class="text-primary">*</span>
                        </label>
                        <input :type="showPass ? 'text' : 'password'"
                               id="new_password"
                               name="password"
                               required
                               placeholder="••••••••"
                               class="w-full px-4 py-2.5 sm:py-3 rounded-2xl border {{ $errors->has('password') ? 'border-rose-400 bg-rose-50/30 text-rose-900' : 'border-slate-200 bg-white text-slate-800' }} text-xs font-semibold focus:border-primary outline-none transition-all">
                        @error('password')
                            <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Xác nhận mật khẩu --}}
                    <div>
                        <label for="new_password_confirmation" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Xác nhận lại mật khẩu mới <span class="text-primary">*</span>
                        </label>
                        <input :type="showPass ? 'text' : 'password'"
                               id="new_password_confirmation"
                               name="password_confirmation"
                               required
                               placeholder="••••••••"
                               class="w-full px-4 py-2.5 sm:py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 text-xs font-semibold focus:border-primary outline-none transition-all">
                    </div>

                    {{-- Checkbox hiện mật khẩu --}}
                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="toggle_password_profile" @change="showPass = !showPass" class="rounded border-slate-300 text-primary focus:ring-primary/20 cursor-pointer">
                        <label for="toggle_password_profile" class="text-xs text-slate-500 select-none cursor-pointer">Hiển thị mật khẩu</label>
                    </div>

                    {{-- Nút hành động trong Popup --}}
                    <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100">
                        <button type="button" @click="passwordModalOpen = false"
                                class="px-5 py-2.5 rounded-2xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                            Hủy bỏ
                        </button>
                        <button type="submit"
                                class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-xs shadow-md shadow-primary/20 hover:shadow-lg transition-all">
                            Cập nhật mật khẩu mới
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

</div>
@endsection
