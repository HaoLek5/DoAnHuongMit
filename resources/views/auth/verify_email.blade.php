@extends('layouts.auth')

@section('title', 'Xác thực Email')
@section('meta_description', 'Xác thực tài khoản Hương Mít bằng mã OTP gửi qua email.')

@section('content')

    {{-- Icon & Heading --}}
    <div class="mb-6 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-pink-50 text-primary mb-3.5 shadow-sm border border-pink-100">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-1">Xác thực tài khoản</h1>
        <p class="text-xs sm:text-sm text-gray-500 max-w-sm mx-auto leading-relaxed">
            Mã OTP 6 số đã được gửi đến hộp thư:<br>
            <span class="font-bold text-gray-800 bg-gray-100 px-2.5 py-0.5 rounded-lg inline-block mt-1">{{ $user->email }}</span>
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

    @if(session('info'))
    <div class="flex items-start gap-3 p-4 mb-5 rounded-2xl bg-blue-50 border border-blue-200 text-xs font-semibold text-blue-800">
        <svg class="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('info') }}</span>
    </div>
    @endif

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

    {{-- Verification Form --}}
    <form method="POST" action="{{ route('verification.verify') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-xs font-bold text-gray-700 mb-2 text-center uppercase tracking-wider">
                Nhập mã xác thực (6 số)
            </label>
            <div class="relative max-w-xs mx-auto">
                <input
                    id="code"
                    type="text"
                    name="code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    pattern="[0-9]*"
                    maxlength="6"
                    autofocus
                    required
                    placeholder="000000"
                    class="w-full text-center tracking-[12px] text-2xl font-mono font-extrabold py-3.5 px-4 rounded-2xl border-2 {{ $errors->has('code') ? 'border-rose-400 bg-rose-50/40 text-rose-900' : 'border-pink-200 bg-pink-50/20 text-gray-900 focus:border-primary focus:bg-white' }} outline-none transition-all shadow-inner placeholder:tracking-normal placeholder:text-gray-300"
                >
            </div>
            @error('code')
            <p class="mt-2 text-xs text-rose-500 font-semibold text-center">{{ $message }}</p>
            @enderror
            <p class="text-[11px] text-gray-400 text-center mt-2">Mã xác thực có hiệu lực trong vòng 15 phút</p>
        </div>

        <div class="pt-2">
            <button
                type="submit"
                class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-sm shadow-md shadow-pink-500/20 hover:shadow-lg transition-all flex items-center justify-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Kích hoạt tài khoản</span>
            </button>
        </div>
    </form>

    {{-- Resend OTP Section with Timer --}}
    <div
        class="mt-6 pt-5 border-t border-gray-100 text-center text-xs text-gray-500"
        x-data="{
            seconds: {{ $secondsUntilResend ?? 0 }},
            timer: null,
            init() {
                if (this.seconds > 0) {
                    this.timer = setInterval(() => {
                        this.seconds--;
                        if (this.seconds <= 0) {
                            clearInterval(this.timer);
                        }
                    }, 1000);
                }
            }
        }"
    >
        <p class="mb-2">Chưa nhận được mã OTP trong hộp thư?</p>
        <form method="POST" action="{{ route('verification.resend') }}">
            @csrf
            <button
                type="submit"
                :disabled="seconds > 0"
                class="font-bold text-primary hover:text-primary-700 disabled:text-gray-400 disabled:cursor-not-allowed transition-colors inline-flex items-center gap-1.5"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-show="seconds <= 0">Gửi lại mã xác thực</span>
                <span x-show="seconds > 0" x-cloak>Gửi lại sau (<span x-text="seconds"></span>s)</span>
            </button>
        </form>
    </div>

    {{-- Change email / Logout --}}
    <div class="mt-4 text-center">
        <form method="POST" action="{{ route('auth.logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-xs text-gray-400 hover:text-gray-600 underline">
                Nhập sai email? Đăng xuất để đăng ký lại
            </button>
        </form>
    </div>

@endsection

