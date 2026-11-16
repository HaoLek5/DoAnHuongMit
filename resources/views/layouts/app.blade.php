<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hương Mít') | Đồ Mẹ & Bé</title>
    <meta name="description" content="@yield('meta_description', 'Hương Mít - Cửa hàng đồ mẹ và bé uy tín, chất lượng cao với giá tốt nhất.')">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary:  { DEFAULT: '#e8638c', 50: '#fdf2f6', 100: '#fce7ef', 200: '#fbd0e1', 300: '#f8a9c7', 400: '#f27aa6', 500: '#e8638c', 600: '#d4426e', 700: '#b83060', 800: '#962855', 900: '#7d244b' },
                        milk:     { DEFAULT: '#f9f4ef', dark: '#ede4d8' },
                        peach:    { DEFAULT: '#f7b89c', light: '#fde8dc' },
                        sage:     { DEFAULT: '#7baa8f', light: '#d4ede0' },
                    },
                    fontFamily: {
                        sans:    ['"Be Vietnam Pro"', 'sans-serif'],
                        display: ['"Playfair Display"', 'serif'],
                    },
                    animation: {
                        'fade-in-up':   'fadeInUp 0.5s ease-out',
                        'fade-in':      'fadeIn 0.4s ease-out',
                        'slide-down':   'slideDown 0.3s ease-out',
                        'float':        'float 3s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeInUp:  { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        fadeIn:    { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        slideDown: { '0%': { opacity: '0', transform: 'translateY(-10px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        float:     { '0%, 100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-8px)' } },
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; }

        /* Gradient text */
        .text-gradient {
            background: linear-gradient(135deg, #e8638c, #f7b89c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Glass effect */
        .glass {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        /* Input focus glow */
        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(232, 99, 140, 0.2);
            border-color: #e8638c;
        }

        /* Flash message */
        .flash-enter { animation: slideDown 0.3s ease-out; }

        /* Custom global scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f9f4ef; }
        ::-webkit-scrollbar-thumb { background: #e8638c; border-radius: 9999px; }

        /* Hover scrollbar (hidden normally, sleek 5px bar on hover) */
        .hover-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: transparent transparent;
            transition: scrollbar-color 0.3s ease;
        }
        .hover-scrollbar::-webkit-scrollbar {
            height: 5px;
            width: 5px;
        }
        .hover-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .hover-scrollbar::-webkit-scrollbar-thumb {
            background: transparent;
            border-radius: 9999px;
            transition: background 0.3s ease;
        }
        .hover-scrollbar:hover {
            scrollbar-color: #e8638c rgba(232, 99, 140, 0.1);
        }
        .hover-scrollbar:hover::-webkit-scrollbar-thumb {
            background: #e8638c;
        }
        .hover-scrollbar:hover::-webkit-scrollbar-track {
            background: rgba(232, 99, 140, 0.1);
            border-radius: 9999px;
        }

        /* Utility to completely hide scrollbar */
        .scrollbar-none {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="bg-milk min-h-screen flex flex-col antialiased selection:bg-primary-100 selection:text-primary-800"
      x-data="globalApp()"
      x-init="init()"
      @cart-updated.window="handleCartUpdate($event)"
      @require-login.window="showLoginModal($event.detail?.message)"
      @show-toast.window="showToast($event.detail?.message)"
      @open-cart.window="openCart()">

    {{-- ── NAVBAR ── --}}
    <header class="glass sticky top-0 z-40 border-b border-primary-100 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary to-peach flex items-center justify-center shadow-md group-hover:scale-105 transition-transform duration-200">
                        <span class="text-white font-bold text-base font-display">HM</span>
                    </div>
                    <div>
                        <span class="font-display text-xl sm:text-2xl text-primary-700 font-bold block leading-none">Hương Mít</span>
                        <span class="text-[10px] text-gray-400 font-medium tracking-wider uppercase block mt-0.5">Mẹ & Bé yêu</span>
                    </div>
                </a>

                {{-- Nav Links --}}
                <nav class="hidden md:flex items-center gap-7 text-sm font-semibold">
                    <a href="{{ route('home') }}" class="text-gray-700 hover:text-primary transition-colors duration-150 {{ request()->routeIs('home') ? 'text-primary font-bold' : '' }}">Trang chủ</a>
                    <a href="{{ route('home') }}#danh-sach-san-pham" class="text-gray-600 hover:text-primary transition-colors duration-150">Sản phẩm</a>
                    <a href="{{ route('home') }}#danh-muc-san-pham" class="text-gray-600 hover:text-primary transition-colors duration-150">Danh mục</a>
                    <a href="{{ route('cart.index') }}" class="text-gray-600 hover:text-primary transition-colors duration-150 {{ request()->routeIs('cart.*') ? 'text-primary font-bold' : '' }}">Giỏ hàng</a>
                </nav>

                {{-- Right actions --}}
                <div class="flex items-center gap-2.5 sm:gap-3.5">
                    
                    {{-- Cart Button (Visible to both guest and auth) --}}
                    <button type="button" @click="openCart()"
                            class="relative p-2.5 rounded-2xl border border-primary-200/80 bg-white/80 hover:bg-primary-50 text-gray-700 hover:text-primary shadow-xs hover:shadow-sm transition-all duration-200 flex items-center gap-2 group">
                        <svg class="w-5 h-5 text-primary group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        
                        <span class="hidden sm:inline text-xs font-bold text-gray-700 group-hover:text-primary">Giỏ hàng</span>
                        
                        <!-- Badge Counter -->
                        <span x-show="cartCount > 0"
                              x-text="cartCount"
                              x-transition:enter="transition ease-out duration-200"
                              x-transition:enter-start="opacity-0 scale-50"
                              x-transition:enter-end="opacity-100 scale-100"
                              class="min-w-[20px] h-5 px-1 rounded-full bg-primary text-white text-[11px] font-bold flex items-center justify-center shadow-xs">
                        </span>
                    </button>

                    @auth
                        {{-- User dropdown --}}
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button @click="open = !open"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-2xl border border-primary-200 bg-white/80 hover:border-primary hover:bg-primary-50 transition-all duration-150">
                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary to-peach flex items-center justify-center text-white text-xs font-bold shadow-xs">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <span class="text-xs font-bold text-gray-700 hidden sm:block max-w-[110px] truncate">{{ auth()->user()->name }}</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50">
                                <div class="px-4 py-2.5 border-b border-gray-100">
                                    <p class="text-[11px] text-gray-400">Tài khoản khách hàng</p>
                                    <p class="text-xs font-bold text-gray-800 truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-[11px] text-gray-500 truncate">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('account.profile') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-primary-50 hover:text-primary transition-colors {{ request()->routeIs('account.profile') ? 'text-primary font-bold bg-primary-50/50' : '' }}">
                                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>Tài khoản của tôi</span>
                                </a>
                                <a href="{{ route('account.addresses.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-primary-50 hover:text-primary transition-colors {{ request()->routeIs('account.addresses.*') ? 'text-primary font-bold bg-primary-50/50' : '' }}">
                                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>Địa chỉ nhận hàng</span>
                                </a>
                                <a href="{{ route('account.orders.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-primary-50 hover:text-primary transition-colors {{ request()->routeIs('account.orders.*') ? 'text-primary font-bold bg-primary-50/50' : '' }}">
                                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                    <span>Đơn hàng của tôi</span>
                                </a>
                                <a href="{{ route('cart.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-primary-50 hover:text-primary transition-colors">
                                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                    <span>Giỏ hàng của tôi (<strong x-text="cartCount"></strong>)</span>
                                </a>
                                @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.statistics.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-primary-700 font-bold hover:bg-primary-50 transition-colors">
                                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Khu vực Quản trị
                                </a>
                                @endif
                                <div class="border-t border-gray-100 mt-1 pt-1">
                                    <form action="{{ route('auth.logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="flex items-center gap-2.5 w-full px-4 py-2 text-xs font-semibold text-rose-500 hover:bg-rose-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            Đăng xuất
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('auth.login') }}"
                           class="text-xs font-bold text-gray-700 hover:text-primary transition-colors px-3 py-2 rounded-xl hover:bg-primary-50">
                            Đăng nhập
                        </a>
                        <a href="{{ route('auth.register') }}"
                           class="text-xs font-bold px-4 py-2 rounded-2xl bg-gradient-to-r from-primary to-primary-600 text-white hover:shadow-md hover:scale-105 transition-all duration-200">
                            Đăng ký
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    {{-- ── FLASH MESSAGES ── --}}
    @if(session('success'))
    <div id="flash-success" class="flash-enter max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4">
        <div class="flex items-center gap-3 p-4 rounded-2xl bg-sage-light border border-sage/30 text-sage-800 shadow-xs">
            <svg class="w-5 h-5 text-sage flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span class="text-xs sm:text-sm font-medium text-gray-700">{{ session('success') }}</span>
            <button onclick="document.getElementById('flash-success').remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </div>
    </div>
    @endif

    @if(session('error') || session('info'))
    <div id="flash-info" class="flash-enter max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4">
        <div class="flex items-center gap-3 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 shadow-xs">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-xs sm:text-sm font-medium text-gray-700">{{ session('error') ?? session('info') }}</span>
            <button onclick="document.getElementById('flash-info').remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </div>
    </div>
    @endif

    {{-- ── MAIN CONTENT ── --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- ── FOOTER ── --}}
    <footer class="bg-white border-t border-primary-100 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 mb-8 items-start">
                
                <!-- Brand Info -->
                <div class="md:col-span-5 space-y-3">
                    <div class="flex items-center gap-2.5 mb-2">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary to-peach flex items-center justify-center shadow-xs">
                            <span class="text-white font-bold text-sm font-display">HM</span>
                        </div>
                        <span class="font-display text-xl text-primary-700 font-bold">Hương Mít</span>
                    </div>
                    <p class="text-xs sm:text-sm text-gray-500 max-w-sm leading-relaxed">
                        Sản phẩm chất lượng cao, an toàn tuyệt đối và chăm sóc tận tâm.
                    </p>
                </div>

                <!-- Contact & Social Links -->
                <div class="md:col-span-3 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-800">Liên hệ & Mua hàng</h4>
                    <ul class="space-y-2.5 text-xs">
                        <li>
                            <a href="https://www.facebook.com/huongmitkid/" target="_blank" rel="noopener noreferrer" 
                               class="inline-flex items-center gap-2 text-gray-600 hover:text-primary transition-colors group">
                                <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 group-hover:bg-primary group-hover:text-white flex items-center justify-center transition-all flex-shrink-0">
                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </span>
                                <div>
                                    <span class="font-bold text-gray-700 group-hover:text-primary block">Facebook</span>
                                    <span class="text-gray-400 text-[11px]">Hương Mít Kid</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="https://zalo.me/0384534989" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 text-gray-600 hover:text-primary transition-colors group">
                                <span class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 group-hover:bg-primary group-hover:text-white flex items-center justify-center transition-all flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </span>
                                <div>
                                    <span class="font-bold text-gray-700 group-hover:text-primary block">Zalo / Hotline</span>
                                    <span class="text-gray-500 text-[11px]">Lê Thị Hương (0384534989)</span>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Store Address -->
                <div class="md:col-span-4 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-800">Địa chỉ cửa hàng</h4>
                    <div class="flex items-start gap-2.5 text-xs text-gray-600 leading-relaxed">
                        <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </span>
                        <div>
                            <span class="font-bold text-gray-700 block mb-0.5">Hương Mít Store</span>
                            <span class="text-gray-500">Số nhà 28, ngách 24, ngõ 1 phố Đồng Me, phường Mễ Trì, quận Nam Từ Liêm, Hà Nội</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="border-t border-slate-100 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-400">
                <p>© {{ date('Y') }} Hương Mít. Tất cả quyền được bảo lưu.</p>
                <div class="flex gap-4">
                    <span>Thương mại điện tử Mẹ & Bé</span>
                </div>
            </div>
        </div>
    </footer>

    {{-- ── SLIDE-OVER CART DRAWER ── --}}
    <div x-show="cartDrawerOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-hidden" 
         aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
        
        <!-- Backdrop -->
        <div x-show="cartDrawerOpen"
             x-transition:enter="ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeCart()"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <!-- Slide-over Panel -->
            <div x-show="cartDrawerOpen"
                 x-transition:enter="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300 sm:duration-400"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-md bg-white shadow-2xl flex flex-col">

                <!-- Drawer Header -->
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-milk">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-primary-100 text-primary flex items-center justify-center font-bold">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-gray-800 font-display">Giỏ hàng của bạn</h3>
                            <p class="text-[11px] text-gray-400" x-text="cartCount + ' sản phẩm đã chọn'"></p>
                        </div>
                    </div>

                    <button type="button" @click="closeCart()" 
                            class="p-2 rounded-xl text-gray-400 hover:text-gray-700 hover:bg-white transition-all">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Drawer Body -->
                <div class="flex-1 overflow-y-auto p-5 space-y-4">
                    
                    <!-- Guest View Notice -->
                    <template x-if="!isLoggedIn">
                        <div class="py-12 px-4 text-center">
                            <div class="w-16 h-16 rounded-full bg-primary-50 text-primary flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <h4 class="font-bold text-gray-800 text-sm mb-1">Bạn chưa đăng nhập</h4>
                            <p class="text-xs text-gray-500 mb-6 max-w-xs mx-auto">
                                Vui lòng đăng nhập để lưu trữ giỏ hàng trong hệ thống và tiến hành mua hàng thuận tiện.
                            </p>
                            <div class="space-y-2">
                                <a href="{{ route('auth.login') }}"
                                   class="block w-full py-2.5 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-600 transition-colors shadow-sm">
                                    Đăng nhập ngay
                                </a>
                                <a href="{{ route('auth.register') }}"
                                   class="block w-full py-2.5 rounded-xl border border-slate-200 text-gray-700 font-bold text-xs hover:bg-slate-50 transition-colors">
                                    Đăng ký tài khoản mới
                                </a>
                            </div>
                        </div>
                    </template>

                    <!-- Empty Cart in Drawer -->
                    <template x-if="isLoggedIn && cartItems.length === 0">
                        <div class="py-16 text-center">
                            <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-gray-700 mb-1">Giỏ hàng của bạn đang trống</p>
                            <p class="text-xs text-gray-400 mb-5">Hãy chọn thêm các sản phẩm yêu thích cho bé nhé!</p>
                            <button type="button" @click="closeCart()"
                                    class="px-5 py-2 rounded-xl bg-primary-50 text-primary font-bold text-xs hover:bg-primary-100 transition-colors">
                                Tiếp tục mua hàng
                            </button>
                        </div>
                    </template>

                    <!-- Items List -->
                    <template x-if="isLoggedIn && cartItems.length > 0">
                        <div class="space-y-3">
                            <template x-for="item in cartItems" :key="item.id">
                                <div class="p-3 rounded-2xl border border-slate-100 bg-slate-50/70 hover:bg-slate-50 flex items-center gap-3 transition-colors">
                                    
                                    <!-- Thumbnail -->
                                    <div class="w-14 h-14 rounded-xl bg-white border border-slate-200/80 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                        <template x-if="item.product && item.product.primary_image">
                                            <img :src="item.product.primary_image.image_url" :alt="item.product.name" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!item.product || !item.product.primary_image">
                                            <svg class="w-6 h-6 text-primary/40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </template>
                                    </div>

                                    <!-- Info -->
                                    <div class="flex-1 min-w-0">
                                        <h5 class="text-xs font-bold text-gray-800 truncate" x-text="item.product ? item.product.name : 'Sản phẩm'"></h5>
                                        <p class="text-[10px] text-primary font-semibold truncate" x-text="item.variant ? item.variant.variant_title : 'Mặc định'"></p>
                                        
                                        <div class="flex items-center justify-between mt-1.5">
                                            <span class="text-xs font-bold text-gray-800" x-text="formatCurrency(item.price)"></span>

                                            <!-- Quantity Mini Stepper -->
                                            <div class="flex items-center border border-slate-200 rounded-lg bg-white overflow-hidden shadow-xs">
                                                <button type="button" @click="updateDrawerQty(item, item.quantity - 1)"
                                                        :disabled="item.quantity <= 1 || item.updating"
                                                        class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-primary disabled:opacity-30">
                                                    -
                                                </button>
                                                <span class="w-6 text-center text-xs font-bold text-gray-700" x-text="item.quantity"></span>
                                                <button type="button" @click="updateDrawerQty(item, item.quantity + 1)"
                                                        :disabled="item.updating"
                                                        class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-primary disabled:opacity-30">
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Item -->
                                    <button type="button" @click="removeDrawerItem(item)"
                                            title="Xóa sản phẩm"
                                            class="p-1.5 text-gray-300 hover:text-rose-500 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>

                </div>

                <!-- Drawer Footer (Only if logged in and has items) -->
                <div x-show="isLoggedIn && cartItems.length > 0" class="p-5 border-t border-slate-100 bg-slate-50/70 space-y-3">
                    <div class="flex items-baseline justify-between text-xs text-gray-500">
                        <span>Tổng tiền thanh toán:</span>
                        <span class="text-lg font-bold text-primary font-sans" x-text="formatCurrency(cartTotal)"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('cart.index') }}" @click="closeCart()"
                           class="py-2.5 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-gray-700 font-bold text-xs text-center transition-all">
                            Xem giỏ hàng
                        </a>
                        <a href="{{ route('checkout.index') }}" @click="closeCart()"
                           class="py-2.5 px-3 rounded-xl bg-gradient-to-r from-primary to-primary-600 text-white font-bold text-xs text-center shadow-xs hover:shadow-md transition-all">
                            Thanh toán ngay
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── REQUIRE LOGIN MODAL ── --}}
    <template x-teleport="body">
        <div x-show="loginModalOpen"
             x-cloak
             class="fixed inset-0 z-[80] overflow-y-auto flex items-center justify-center p-4"
             role="dialog" aria-modal="true">
            
            <div x-show="loginModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 @click="loginModalOpen = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

            <div x-show="loginModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative bg-white rounded-3xl p-6 sm:p-8 max-w-sm w-full text-center shadow-2xl border border-primary-100 z-10">
                
                <div class="w-16 h-16 rounded-full bg-primary-100 text-primary flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>

                <h3 class="font-bold text-base text-gray-800 font-display mb-1.5">Yêu cầu đăng nhập</h3>
                <p class="text-xs text-gray-500 mb-6 leading-relaxed" x-text="loginModalMessage"></p>

                <div class="space-y-2">
                    <a :href="'{{ route('auth.login') }}?redirect=' + encodeURIComponent(window.location.href)"
                       class="block w-full py-3 rounded-2xl bg-gradient-to-r from-primary to-primary-600 text-white font-bold text-xs shadow-md hover:shadow-lg transition-all">
                        Đăng nhập ngay
                    </a>
                    <a :href="'{{ route('auth.register') }}?redirect=' + encodeURIComponent(window.location.href)"
                       class="block w-full py-2.5 rounded-2xl border border-slate-200 hover:bg-slate-50 text-gray-700 font-bold text-xs transition-colors">
                        Chưa có tài khoản? Đăng ký
                    </a>
                    <button type="button" @click="loginModalOpen = false"
                            class="text-[11px] text-gray-400 hover:text-gray-600 pt-2 block mx-auto">
                        Để sau
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ── GLOBAL TOAST NOTIFICATION ── --}}
    <template x-teleport="body">
        <div x-show="toastVisible"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:translate-x-4"
             x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed bottom-24 right-5 sm:bottom-28 sm:right-6 z-[90] max-w-sm bg-white rounded-2xl shadow-xl border border-primary-200 p-4 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="flex-1 text-xs font-semibold text-gray-800" x-text="toastMessage"></div>
            <button type="button" @click="toastVisible = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>

    {{-- ── PRODUCT RECOMMENDATION CHATBOT ── --}}
    @include('components.chatbot')

    {{-- Alpine.js for dropdown --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
    function globalApp() {
        return {
            isLoggedIn: {{ Auth::check() ? 'true' : 'false' }},
            cartDrawerOpen: false,
            loginModalOpen: false,
            loginModalMessage: 'Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng và mua sắm trên Hương Mít.',
            
            cartCount: 0,
            cartTotal: 0,
            cartItems: [],

            toastVisible: false,
            toastMessage: '',
            toastTimeout: null,

            init() {
                if (this.isLoggedIn) {
                    this.fetchCart();
                }
            },

            formatCurrency(num) {
                return new Intl.NumberFormat('vi-VN').format(num || 0) + ' đ';
            },

            async fetchCart() {
                try {
                    const res = await fetch('/gio-hang/data', {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.cartCount = data.count || 0;
                        this.cartTotal = data.total || 0;
                        this.cartItems = data.items || [];
                    }
                } catch (e) {
                    console.error('Lỗi tải giỏ hàng:', e);
                }
            },

            openCart() {
                this.cartDrawerOpen = true;
                if (this.isLoggedIn) {
                    this.fetchCart();
                }
            },

            closeCart() {
                this.cartDrawerOpen = false;
            },

            showLoginModal(msg) {
                if (msg) this.loginModalMessage = msg;
                this.loginModalOpen = true;
            },

            showToast(msg) {
                this.toastMessage = msg;
                this.toastVisible = true;
                clearTimeout(this.toastTimeout);
                this.toastTimeout = setTimeout(() => {
                    this.toastVisible = false;
                }, 3500);
            },

            handleCartUpdate(event) {
                if (event.detail) {
                    this.cartCount = event.detail.count ?? this.cartCount;
                    this.cartTotal = event.detail.total ?? this.cartTotal;
                    if (event.detail.items) {
                        this.cartItems = event.detail.items;
                    }
                }
            },

            async updateDrawerQty(item, newQty) {
                if (newQty < 1) return;
                item.updating = true;
                try {
                    const res = await fetch(`/gio-hang/${item.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ quantity: newQty })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.cartCount = data.count;
                        this.cartTotal = data.total;
                        this.cartItems = data.items;
                        window.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
                    } else {
                        alert(data.message || 'Không thể cập nhật số lượng.');
                    }
                } catch (e) {
                    alert('Lỗi: ' + e.message);
                } finally {
                    item.updating = false;
                }
            },

            async removeDrawerItem(item) {
                try {
                    const res = await fetch(`/gio-hang/${item.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.cartCount = data.count;
                        this.cartTotal = data.total;
                        this.cartItems = data.items;
                        window.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
                    }
                } catch (e) {
                    alert('Lỗi: ' + e.message);
                }
            }
        };
    }
    </script>

    @stack('scripts')
</body>
</html>
