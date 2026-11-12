@extends('layouts.auth')

@section('title', 'Đăng ký tài khoản')
@section('meta_description', 'Tạo tài khoản Hương Mít để mua sắm đồ mẹ và bé tiện lợi hơn.')

@section('content')

    {{-- Heading --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Tạo tài khoản mới</h1>
        <p class="text-sm text-gray-500">Tham gia cùng hàng nghìn gia đình tin dùng Hương Mít.</p>
    </div>

    {{-- Register Form --}}
    <form id="register-form" method="POST" action="{{ route('auth.register.post') }}" novalidate>
        @csrf

        {{-- Full Name --}}
        <div class="mb-4">
            <label for="reg-name" class="block text-sm font-medium text-gray-700 mb-1.5">
                Họ và tên <span class="text-primary">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <input
                    id="reg-name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    autocomplete="name"
                    placeholder="Nguyễn Thị Hào"
                    class="input-field w-full pl-10 pr-4 py-3 rounded-xl border {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }} text-sm focus:bg-white"
                >
            </div>
            @error('name')
            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ $message }}
            </p>
            @enderror
        </div>

        {{-- Email --}}
        <div class="mb-4">
            <label for="reg-email" class="block text-sm font-medium text-gray-700 mb-1.5">
                Email <span class="text-primary">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                    </svg>
                </div>
                <input
                    id="reg-email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    placeholder="example@email.com"
                    class="input-field w-full pl-10 pr-4 py-3 rounded-xl border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }} text-sm focus:bg-white"
                >
            </div>
            @error('email')
            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ $message }}
            </p>
            @enderror
        </div>

        {{-- Phone (optional) --}}
        <div class="mb-4">
            <label for="reg-phone" class="block text-sm font-medium text-gray-700 mb-1.5">
                Số điện thoại
                <span class="text-gray-400 font-normal">(không bắt buộc)</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
                <input
                    id="reg-phone"
                    type="tel"
                    name="phone"
                    value="{{ old('phone') }}"
                    autocomplete="tel"
                    placeholder="0912 345 678"
                    class="input-field w-full pl-10 pr-4 py-3 rounded-xl border {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }} text-sm focus:bg-white"
                >
            </div>
            @error('phone')
            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ $message }}
            </p>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-4" x-data="passwordStrength()">
            <label for="reg-password" class="block text-sm font-medium text-gray-700 mb-1.5">
                Mật khẩu <span class="text-primary">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <input
                    id="reg-password"
                    :type="show ? 'text' : 'password'"
                    name="password"
                    autocomplete="new-password"
                    placeholder="Tối thiểu 8 ký tự"
                    @input="checkStrength($event.target.value)"
                    class="input-field w-full pl-10 pr-11 py-3 rounded-xl border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }} text-sm focus:bg-white"
                >
                <button type="button" @click="show = !show"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-6.93 3.07A9.952 9.952 0 0112 17c2.757 0 5.257-1.115 7.071-2.929M3 12c0-4.418 4.03-8 9-8s9 3.582 9 8-4.03 8-9 8-9-3.582-9-8z"/></svg>
                </button>
            </div>

            {{-- Password strength indicator --}}
            <div x-show="password.length > 0" x-transition class="mt-2">
                <div class="flex gap-1 mb-1">
                    <div class="flex-1 h-1 rounded-full bg-gray-200 overflow-hidden">
                        <div class="strength-bar h-full rounded-full" :style="{ width: score >= 1 ? '100%' : '0%', backgroundColor: barColor(1) }"></div>
                    </div>
                    <div class="flex-1 h-1 rounded-full bg-gray-200 overflow-hidden">
                        <div class="strength-bar h-full rounded-full" :style="{ width: score >= 2 ? '100%' : '0%', backgroundColor: barColor(2) }"></div>
                    </div>
                    <div class="flex-1 h-1 rounded-full bg-gray-200 overflow-hidden">
                        <div class="strength-bar h-full rounded-full" :style="{ width: score >= 3 ? '100%' : '0%', backgroundColor: barColor(3) }"></div>
                    </div>
                    <div class="flex-1 h-1 rounded-full bg-gray-200 overflow-hidden">
                        <div class="strength-bar h-full rounded-full" :style="{ width: score >= 4 ? '100%' : '0%', backgroundColor: barColor(4) }"></div>
                    </div>
                </div>
                <p class="text-xs" :class="strengthTextClass()" x-text="strengthLabel()"></p>
            </div>

            @error('password')
            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ $message }}
            </p>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="mb-6" x-data="{ show: false }">
            <label for="reg-password-confirm" class="block text-sm font-medium text-gray-700 mb-1.5">
                Xác nhận mật khẩu <span class="text-primary">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <input
                    id="reg-password-confirm"
                    :type="show ? 'text' : 'password'"
                    name="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Nhập lại mật khẩu"
                    class="input-field w-full pl-10 pr-11 py-3 rounded-xl border {{ $errors->has('password_confirmation') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }} text-sm focus:bg-white"
                >
                <button type="button" @click="show = !show"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-6.93 3.07A9.952 9.952 0 0112 17c2.757 0 5.257-1.115 7.071-2.929M3 12c0-4.418 4.03-8 9-8s9 3.582 9 8-4.03 8-9 8-9-3.582-9-8z"/></svg>
                </button>
            </div>
        </div>

        {{-- Submit --}}
        <button id="register-submit-btn" type="submit"
            class="btn-primary w-full py-3.5 rounded-xl text-white font-semibold text-sm tracking-wide shadow-lg">
            <span class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Tạo tài khoản
            </span>
        </button>

        {{-- Terms note --}}
        <p class="text-center text-xs text-gray-400 mt-4">
            Bằng cách đăng ký, bạn đồng ý với
            <a href="#" class="text-primary hover:underline">Điều khoản dịch vụ</a>
            và
            <a href="#" class="text-primary hover:underline">Chính sách bảo mật</a> của chúng tôi.
        </p>
    </form>

    {{-- Divider --}}
    <div class="flex items-center gap-3 my-6">
        <div class="flex-1 h-px bg-gray-200"></div>
        <span class="text-xs text-gray-400">đã có tài khoản?</span>
        <div class="flex-1 h-px bg-gray-200"></div>
    </div>

    {{-- Login link --}}
    <p class="text-center text-sm text-gray-600">
        <a href="{{ route('auth.login') }}" class="font-semibold text-primary hover:text-primary-700 transition-colors">
            ← Đăng nhập ngay
        </a>
    </p>

@endsection

@push('scripts')
<script>
    function passwordStrength() {
        return {
            show: false,
            password: '',
            score: 0,

            checkStrength(val) {
                this.password = val;
                let score = 0;
                if (val.length >= 8)  score++;
                if (val.length >= 12) score++;
                if (/[A-Z]/.test(val)) score++;
                if (/[0-9]/.test(val)) score++;
                if (/[^A-Za-z0-9]/.test(val)) score++;
                this.score = Math.min(4, score);
            },

            barColor(bar) {
                const colors = ['', '#ef4444', '#f97316', '#eab308', '#22c55e'];
                return this.score >= bar ? colors[this.score] || '#22c55e' : '#e5e7eb';
            },

            strengthLabel() {
                const labels = ['', 'Rất yếu', 'Yếu', 'Trung bình', 'Mạnh'];
                return labels[this.score] || 'Rất mạnh';
            },

            strengthTextClass() {
                const classes = ['', 'text-red-500', 'text-orange-500', 'text-yellow-500', 'text-green-500'];
                return classes[this.score] || 'text-green-500';
            }
        }
    }

    // Loading state on submit
    const form = document.getElementById('register-form');
    const btn  = document.getElementById('register-submit-btn');

    form.addEventListener('submit', () => {
        btn.disabled = true;
        btn.innerHTML = `<span class="flex items-center justify-center gap-2">
            <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            Đang tạo tài khoản...
        </span>`;
    });
</script>
@endpush
