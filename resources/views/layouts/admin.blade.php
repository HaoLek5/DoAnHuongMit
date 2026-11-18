<!DOCTYPE html>
<html lang="vi" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản trị hệ thống') | Hương Mít Admin</title>

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
                        slate: { 850: '#151e2e', 900: '#0f172a' }
                    },
                    fontFamily: {
                        sans: ['"Be Vietnam Pro"', 'sans-serif'],
                        display: ['"Playfair Display"', 'serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; }
        .glass-header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
        }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
</head>
<body class="h-full antialiased text-slate-800" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden" 
         @click="sidebarOpen = false" style="display: none;"></div>

    <div class="flex h-full">
        <!-- Sidebar Navigation -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
               class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 text-slate-300 transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 flex flex-col shadow-2xl">
            
            <!-- Brand Logo -->
            <div class="h-20 flex items-center justify-between px-6 border-b border-slate-800">
                <a href="{{ route('admin.statistics.index') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-primary to-rose-400 flex items-center justify-center shadow-lg shadow-primary/20 group-hover:scale-105 transition-transform">
                        <span class="text-white font-black text-base tracking-wider">HM</span>
                    </div>
                    <div>
                        <div class="font-display font-bold text-white text-lg tracking-wide">Hương Mít</div>
                        <div class="text-[11px] font-semibold tracking-wider uppercase text-primary-400">Admin Portal</div>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                <div class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Bảng điều khiển</div>

                <a href="{{ route('admin.statistics.index') }}" 
                   class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('admin.statistics.*') || request()->routeIs('admin.dashboard') ? 'bg-primary text-white shadow-lg shadow-primary/30 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Thống kê</span>
                </a>

                <div class="pt-5 px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Quản lý bán hàng</div>

                <a href="{{ route('admin.products.index') }}" 
                   class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('admin.products.*') ? 'bg-primary text-white shadow-lg shadow-primary/30 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>Sản phẩm</span>
                </a>

                <a href="{{ route('admin.sale-campaigns.index') }}" 
                   class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('admin.sale-campaigns.*') ? 'bg-primary text-white shadow-lg shadow-primary/30 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                    </svg>
                    <span>Chiến dịch Sale</span>
                    @php 
                        $runningSaleCount = \App\Models\SaleCampaign::running()->count(); 
                    @endphp
                    @if($runningSaleCount > 0)
                        <span class="ml-auto text-[10px] font-bold bg-amber-500 text-white px-2 py-0.5 rounded-full shadow-xs flex items-center gap-0.5">
                            🔥 {{ $runningSaleCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('admin.stock-receipts.index') }}" 
                   class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('admin.stock-receipts.*') ? 'bg-primary text-white shadow-lg shadow-primary/30 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20"/>
                    </svg>
                    <span>Nhập kho</span>
                </a>

                <a href="{{ route('admin.inventory.index') }}" 
                   class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('admin.inventory.*') ? 'bg-primary text-white shadow-lg shadow-primary/30 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span>Kiểm kho</span>
                </a>

                <a href="{{ route('admin.orders.index') }}" 
                   class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('admin.orders.*') ? 'bg-primary text-white shadow-lg shadow-primary/30 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    <span>Đơn hàng</span>
                    @php $pendingOrdersCount = \App\Models\Order::where('order_status', 'pending')->count(); @endphp
                    @if($pendingOrdersCount > 0)
                        <span class="ml-auto text-[10px] font-bold bg-rose-500 text-white px-2 py-0.5 rounded-full shadow-xs">
                            {{ $pendingOrdersCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('admin.bank-transactions.index') }}"
                   class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('admin.bank-transactions.*') ? 'bg-primary text-white shadow-lg shadow-primary/30 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <span>Đối soát VietQR</span>
                    @php
                        $needsReviewTxCount = \Illuminate\Support\Facades\Schema::hasTable('bank_transactions')
                            ? \App\Models\BankTransaction::where('reconciliation_status', 'needs_review')->count()
                            : 0;
                    @endphp
                    @if($needsReviewTxCount > 0)
                        <span class="ml-auto text-[10px] font-bold bg-amber-500 text-white px-2 py-0.5 rounded-full shadow-xs">
                            {{ $needsReviewTxCount }}
                        </span>
                    @endif
                </a>

                <div class="pt-5 px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-500">Hệ thống & Người dùng</div>

                <a href="{{ route('admin.customers.index') }}" 
                   class="flex items-center gap-3.5 px-3.5 py-3 rounded-xl font-medium text-sm transition-all {{ request()->routeIs('admin.customers.*') ? 'bg-primary text-white shadow-lg shadow-primary/30 font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>Tài khoản khách</span>
                </a>
            </nav>

            <!-- Bottom Website Link -->
            <div class="p-4 border-t border-slate-800">
                <a href="{{ route('home') }}" target="_blank" 
                   class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold text-slate-300 bg-slate-800/80 hover:bg-slate-700/80 hover:text-white transition-all">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Xem Website Cửa Hàng
                    </span>
                    <span class="text-[10px] text-slate-400">↗</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Header Bar -->
            <header class="glass-header sticky top-0 z-30 h-20 border-b border-slate-200/80 flex items-center justify-between px-4 sm:px-8">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">@yield('page_title', 'Bảng điều khiển quản trị')</h1>
                    </div>
                </div>

                <!-- Admin Profile & Actions -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <div class="hidden md:flex items-center gap-2 px-3 py-1.5 bg-rose-50 border border-rose-100 rounded-full text-xs font-semibold text-primary">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        Quyền: Quản trị viên cao cấp
                    </div>

                    <!-- Profile Dropdown -->
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" class="flex items-center gap-2.5 p-1.5 rounded-full hover:bg-slate-100 transition-colors">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-primary to-rose-400 flex items-center justify-center text-white font-bold text-sm shadow">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div class="text-left hidden sm:block">
                                <div class="text-xs font-bold text-slate-800 leading-tight">{{ auth()->user()->name }}</div>
                                <div class="text-[10px] text-slate-400 leading-tight">{{ auth()->user()->email }}</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50" style="display: none;">
                            
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-[11px] text-slate-400">Đang đăng nhập:</p>
                                <p class="text-xs font-bold text-slate-700 truncate">{{ auth()->user()->email }}</p>
                            </div>

                            <a href="{{ route('home') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs text-slate-600 hover:bg-slate-50 hover:text-primary transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                Ra trang bán hàng
                            </a>

                            <div class="border-t border-slate-100 my-1"></div>

                            <form action="{{ route('auth.logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2.5 text-xs text-rose-600 hover:bg-rose-50 transition-colors font-semibold">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Đăng xuất hệ thống
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Toast Popup Notifications -->
            @if(session('success') || session('error') || session('warning') || session('info'))
            <div x-data="{ show: true }" 
                 x-init="setTimeout(() => show = false, 4000)"
                 x-show="show" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-4"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed top-5 right-5 z-50 max-w-sm w-full bg-white rounded-2xl shadow-2xl border p-4 flex items-start gap-3 pointer-events-auto @if(session('error')) border-rose-200 @elseif(session('warning')) border-amber-200 @elseif(session('info')) border-sky-200 @else border-emerald-200 @endif"
                 role="alert">
                
                @if(session('success'))
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div class="flex-1 min-w-0 pt-0.5">
                        <h4 class="text-xs font-bold text-slate-900">Thành công</h4>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">{{ session('success') }}</p>
                    </div>
                @elseif(session('error'))
                    <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <div class="flex-1 min-w-0 pt-0.5">
                        <h4 class="text-xs font-bold text-slate-900">Thông báo lỗi</h4>
                        <p class="text-xs text-rose-600 mt-0.5 leading-relaxed">{{ session('error') }}</p>
                    </div>
                @elseif(session('warning'))
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0 pt-0.5">
                        <h4 class="text-xs font-bold text-slate-900">Cảnh báo</h4>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">{{ session('warning') }}</p>
                    </div>
                @elseif(session('info'))
                    <div class="w-8 h-8 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0 pt-0.5">
                        <h4 class="text-xs font-bold text-slate-900">Thông tin</h4>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">{{ session('info') }}</p>
                    </div>
                @endif

                <button type="button" @click="show = false" class="text-slate-400 hover:text-slate-600 p-1 -mr-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            @endif

            <!-- Page Body -->
            <main class="flex-1 overflow-y-auto px-4 sm:px-8 pb-12">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
