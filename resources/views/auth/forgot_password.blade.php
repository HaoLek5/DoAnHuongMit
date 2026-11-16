@extends('layouts.auth')

@section('title', 'Quên mật khẩu')
@section('meta_description', 'Khôi phục mật khẩu tài khoản Hương Mít bằng mã xác thực gửi qua email.')

@section('content')

    {{-- Icon & Heading --}}
    <div class="mb-6 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-pink-50 text-primary mb-3.5 shadow-sm border border-pink-100">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Quên mật khẩu?</h1>
        <p class="text-xs sm:text-sm text-gray-500 max-w-sm mx-auto leading-relaxed">
            Đừng lo lắng! Vui lòng nhập địa chỉ email bạn đã dùng để đăng ký. Hương Mít sẽ gửi cho bạn mã OTP 6 số để tạo mật khẩu mới.
        </p>
    </div>

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

    {{-- Form --}}
    <form method="POST" action="{{ route('auth.forgot-password.send') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="forgot-email" class="block text-xs font-bold text-gray-700 mb-1.5">
                Địa chỉ Email <span class="text-primary">*</span>
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                    </svg>
                </div>
                <input
                    id="forgot-email"
                    type="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    autocomplete="email"
                    required
                    autofocus
                    placeholder="example@email.com"
                    class="w-full pl-10 pr-4 py-3 rounded-2xl border {{ $errors->has('email') ? 'border-rose-400 bg-rose-50/40 text-rose-900' : 'border-gray-200 bg-gray-50/50 text-gray-800 focus:bg-white focus:border-primary' }} text-xs font-semibold outline-none transition-all"
                >
            </div>
            @error('email')
                <p class="text-[11px] text-rose-500 font-semibold mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <button
                type="submit"
                class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-xs shadow-md shadow-pink-500/20 hover:shadow-lg transition-all flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <span>Gửi mã xác thực qua Email</span>
            </button>
        </div>
    </form>

    {{-- Divider --}}
    <div class="flex items-center gap-3 my-6">
        <div class="flex-1 h-px bg-gray-200"></div>
        <span class="text-xs text-gray-400">hoặc</span>
        <div class="flex-1 h-px bg-gray-200"></div>
    </div>

    {{-- Back to login --}}
    <p class="text-center text-xs text-gray-600">
        Bạn đã nhớ lại mật khẩu?
        <a href="{{ route('auth.login') }}" class="font-bold text-primary hover:text-primary-700 transition-colors ml-1">
            Đăng nhập ngay →
        </a>
    </p>

@endsection

