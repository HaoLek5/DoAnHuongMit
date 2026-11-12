@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6 font-medium">
        <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Trang chủ</a>
        <span>/</span>
        <a href="{{ route('account.profile') }}" class="hover:text-primary transition-colors">Tài khoản</a>
        <span>/</span>
        <span class="text-primary font-bold">@yield('page_name', 'Hồ sơ cá nhân')</span>
    </nav>

    <div class="flex flex-col lg:flex-row items-start gap-8">
        
        <!-- ── ACCOUNT SIDEBAR ── -->
        <aside class="w-full lg:w-72 flex-shrink-0">
            <div class="bg-white rounded-3xl border border-primary-100/80 p-6 shadow-xs sticky top-24 space-y-6">
                
                <!-- User Quick Info -->
                <div class="flex items-center gap-3.5 pb-6 border-b border-slate-100">
                    <div class="w-13 h-13 rounded-2xl bg-gradient-to-br from-primary to-rose-400 flex items-center justify-center text-white text-lg font-bold shadow-md shadow-primary/20">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs text-slate-400 font-medium">Xin chào,</div>
                        <h3 class="text-sm font-bold text-slate-800 truncate">{{ auth()->user()->name }}</h3>
                        <p class="text-[11px] text-slate-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>

                <!-- Navigation Menu -->
                <nav class="space-y-1.5 text-xs font-semibold">
                    
                    <!-- Profile Link -->
                    <a href="{{ route('account.profile') }}" 
                       class="flex items-center justify-between px-3.5 py-3 rounded-2xl transition-all {{ request()->routeIs('account.profile') ? 'bg-primary text-white shadow-md shadow-primary/25 font-bold' : 'text-slate-600 hover:text-primary hover:bg-primary-50/60' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 {{ request()->routeIs('account.profile') ? 'text-white' : 'text-primary' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>Thông tin tài khoản</span>
                        </div>
                        <svg class="w-3.5 h-3.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <!-- Address Book Link -->
                    <a href="{{ route('account.addresses.index') }}" 
                       class="flex items-center justify-between px-3.5 py-3 rounded-2xl transition-all {{ request()->routeIs('account.addresses.*') ? 'bg-primary text-white shadow-md shadow-primary/25 font-bold' : 'text-slate-600 hover:text-primary hover:bg-primary-50/60' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 {{ request()->routeIs('account.addresses.*') ? 'text-white' : 'text-primary' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Địa chỉ nhận hàng</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ request()->routeIs('account.addresses.*') ? 'bg-white/20 text-white' : 'bg-primary-50 text-primary' }}">
                            {{ auth()->user()->addresses()->count() }}
                        </span>
                    </a>

                    <!-- Orders Link -->
                    <a href="{{ route('account.orders.index') }}" 
                       class="flex items-center justify-between px-3.5 py-3 rounded-2xl transition-all {{ request()->routeIs('account.orders.*') ? 'bg-primary text-white shadow-md shadow-primary/25 font-bold' : 'text-slate-600 hover:text-primary hover:bg-primary-50/60' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 {{ request()->routeIs('account.orders.*') ? 'text-white' : 'text-primary' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <span>Đơn hàng của tôi</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ request()->routeIs('account.orders.*') ? 'bg-white/20 text-white' : 'bg-primary-50 text-primary' }}">
                            {{ auth()->user()->orders()->count() }}
                        </span>
                    </a>

                    <!-- Cart Link -->
                    <a href="{{ route('cart.index') }}" 
                       class="flex items-center justify-between px-3.5 py-3 rounded-2xl text-slate-600 hover:text-primary hover:bg-primary-50/60 transition-all">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>Giỏ hàng của tôi</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600" x-text="cartCount"></span>
                    </a>

                    @if(auth()->user()->isAdmin())
                    <!-- Admin Link -->
                    <a href="{{ route('admin.statistics.index') }}" 
                       class="flex items-center justify-between px-3.5 py-3 rounded-2xl text-primary font-bold bg-primary-50/40 hover:bg-primary-50 transition-all">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Trang quản trị Admin</span>
                        </div>
                        <span class="text-[10px] font-bold text-primary">↗</span>
                    </a>
                    @endif

                    <!-- Logout Button -->
                    <div class="pt-2 border-t border-slate-100">
                        <form action="{{ route('auth.logout') }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full flex items-center justify-between px-3.5 py-3 rounded-2xl text-rose-500 hover:bg-rose-50 font-bold transition-all">
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    <span>Đăng xuất</span>
                                </div>
                            </button>
                        </form>
                    </div>

                </nav>

            </div>
        </aside>

        <!-- ── MAIN CONTENT ── -->
        <main class="flex-1 min-w-0 w-full space-y-6">
            @yield('account_content')
        </main>

    </div>

</div>
@endsection
