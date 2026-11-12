@extends('layouts.auth')

@section('title', 'Đăng nhập')
@section('meta_description', 'Đăng nhập vào tài khoản Hương Mít của bạn.')

@section('content')

    {{-- Heading --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Chào mừng trở lại!</h1>
        <p class="text-sm text-gray-500">Đăng nhập để tiếp tục mua sắm cùng Hương Mít.</p>
    </div>

    {{-- Global Error Alert --}}
    @if($errors->any())
    <div id="login-error-alert" class="flex items-start gap-3 p-4 mb-5 rounded-2xl bg-red-50 border border-red-200 shake">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div class="flex-1">
            @foreach($errors->all() as $error)
            <p class="text-sm text-red-700">{{ $error }}</p>
            @endforeach
        </div>
        <button onclick="document.getElementById('login-error-alert').remove()" class="text-red-400 hover:text-red-600">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
        </button>
    </div>
    @endif

    {{-- Login Form --}}
    <form id="login-form" method="POST" action="{{ route('auth.login.post') }}" novalidate>
        @csrf

        {{-- Email --}}
        <div class="mb-4">
            <label for="login-email" class="block text-sm font-medium text-gray-700 mb-1.5">
                Email <span class="text-primary">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                    </svg>
                </div>
                <input
                    id="login-email"
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

        {{-- Password --}}
        <div class="mb-4">
            <label for="login-password" class="block text-sm font-medium text-gray-700 mb-1.5">
                Mật khẩu <span class="text-primary">*</span>
            </label>
            <div class="relative" x-data="{ show: false }">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <input
                    id="login-password"
                    :type="show ? 'text' : 'password'"
                    name="password"
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="input-field w-full pl-10 pr-11 py-3 rounded-xl border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }} text-sm focus:bg-white"
                >
                <button type="button" @click="show = !show"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-6.93 3.07A9.952 9.952 0 0112 17c2.757 0 5.257-1.115 7.071-2.929M3 3l18 18M9.88 9.88A3 3 0 0112 9m0 0a3 3 0 013 3"/></svg>
                    <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-6.93 3.07A9.952 9.952 0 0112 17c2.757 0 5.257-1.115 7.071-2.929M3 12c0-4.418 4.03-8 9-8s9 3.582 9 8-4.03 8-9 8-9-3.582-9-8z"/></svg>
                </button>
            </div>
            @error('password')
            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ $message }}
            </p>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="flex items-center mb-6">
            <input id="remember" name="remember" type="checkbox"
                class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary focus:ring-offset-0 cursor-pointer">
            <label for="remember" class="ml-2 text-sm text-gray-600 cursor-pointer select-none">
                Ghi nhớ đăng nhập
            </label>
        </div>

        {{-- Submit --}}
        <button id="login-submit-btn" type="submit"
            class="btn-primary w-full py-3.5 rounded-xl text-white font-semibold text-sm tracking-wide shadow-lg">
            <span class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                Đăng nhập
            </span>
        </button>
    </form>

    {{-- Divider --}}
    <div class="flex items-center gap-3 my-6">
        <div class="flex-1 h-px bg-gray-200"></div>
        <span class="text-xs text-gray-400">hoặc</span>
        <div class="flex-1 h-px bg-gray-200"></div>
    </div>

    {{-- Register link --}}
    <p class="text-center text-sm text-gray-600">
        Chưa có tài khoản?
        <a href="{{ route('auth.register') }}" class="font-semibold text-primary hover:text-primary-700 transition-colors">
            Đăng ký ngay →
        </a>
    </p>

@endsection

@push('scripts')
<script>
    // Loading state on submit
    const form = document.getElementById('login-form');
    const btn  = document.getElementById('login-submit-btn');

    form.addEventListener('submit', () => {
        btn.disabled = true;
        btn.innerHTML = `<span class="flex items-center justify-center gap-2">
            <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            Đang đăng nhập...
        </span>`;
    });
</script>
@endpush
