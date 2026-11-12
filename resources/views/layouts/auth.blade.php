<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hương Mít') | Đồ Mẹ & Bé</title>
    <meta name="description" content="@yield('meta_description', 'Hương Mít - Đăng nhập và đăng ký tài khoản.')">

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
                        'fade-in-up': 'fadeInUp 0.6s ease-out',
                        'fade-in':    'fadeIn 0.5s ease-out',
                        'float':      'float 4s ease-in-out infinite',
                        'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeInUp:   { '0%': { opacity: '0', transform: 'translateY(24px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } },
                        fadeIn:     { '0%': { opacity: '0' }, '100%': { opacity: '1' } },
                        float:      { '0%, 100%': { transform: 'translateY(0px)' }, '50%': { transform: 'translateY(-12px)' } },
                        pulseSoft:  { '0%, 100%': { opacity: '0.6' }, '50%': { opacity: '1' } },
                    }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; }

        .text-gradient {
            background: linear-gradient(135deg, #e8638c, #f7b89c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .bg-auth-pattern {
            background-color: #fdf2f6;
            background-image:
                radial-gradient(circle at 20% 20%, rgba(232,99,140,0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(247,184,156,0.2) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(123,170,143,0.08) 0%, transparent 60%);
        }

        .card-auth {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
        }

        .input-field {
            transition: all 0.2s ease;
        }

        .input-field:focus {
            box-shadow: 0 0 0 3px rgba(232, 99, 140, 0.18);
            border-color: #e8638c;
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #e8638c, #d4426e);
            transition: all 0.25s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #d4426e, #b83060);
            box-shadow: 0 8px 24px rgba(232, 99, 140, 0.4);
            transform: translateY(-1px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Decorative blobs */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.4;
            pointer-events: none;
        }

        /* Password strength bar */
        .strength-bar { transition: width 0.3s ease, background-color 0.3s ease; }

        /* Error shake */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .shake { animation: shake 0.3s ease; }
    </style>
</head>
<body class="bg-auth-pattern min-h-screen flex flex-col">

    {{-- Decorative blobs --}}
    <div class="blob w-80 h-80 bg-primary-200 top-0 left-0 -translate-x-1/2 -translate-y-1/2"></div>
    <div class="blob w-64 h-64 bg-peach-light bottom-0 right-0 translate-x-1/3 translate-y-1/3"></div>

    {{-- Back to home --}}
    <div class="relative z-10 p-4">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-primary transition-colors duration-150">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Về trang chủ
        </a>
    </div>

    {{-- Center content --}}
    <div class="relative z-10 flex-1 flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">

            {{-- Logo --}}
            <div class="text-center mb-8 animate-fade-in">
                <a href="{{ route('home') }}" class="inline-flex flex-col items-center gap-2">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary to-peach flex items-center justify-center shadow-xl animate-float">
                        <span class="text-white font-bold text-xl">HM</span>
                    </div>
                    <span class="font-display text-2xl text-primary-700 font-semibold">Hương Mít</span>
                    <span class="text-xs text-gray-400 tracking-wider uppercase">Đồ Mẹ & Bé</span>
                </a>
            </div>

            {{-- Card --}}
            <div class="card-auth rounded-3xl shadow-2xl border border-white/60 p-8 animate-fade-in-up">
                @yield('content')
            </div>

            {{-- Footer note --}}
            <p class="text-center text-xs text-gray-400 mt-6">
                © {{ date('Y') }} Hương Mít. Tất cả quyền được bảo lưu.
            </p>
        </div>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
