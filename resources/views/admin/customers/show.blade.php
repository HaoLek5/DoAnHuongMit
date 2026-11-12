@extends('layouts.admin')

@section('title', 'Khách hàng: ' . $customer->name . ' - Hương Mít')
@section('page_title', 'Chi tiết tài khoản khách hàng')

@section('content')
<div x-data="{ editModal: false, passwordModal: false }" class="space-y-6">

    {{-- Breadcrumbs & Back Button --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-primary transition-colors">Tổng quan</a>
            <span>/</span>
            <a href="{{ route('admin.customers.index') }}" class="hover:text-primary transition-colors">Tài khoản khách</a>
            <span>/</span>
            <span class="font-bold text-slate-800">{{ $customer->name }}</span>
        </div>

        <a href="{{ route('admin.customers.index') }}" 
           class="px-3.5 py-2 rounded-2xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Quay lại danh sách</span>
        </a>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2.5 shadow-xs">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800 flex items-center gap-2.5 shadow-xs">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Profile Hero Banner Card --}}
    @php
        $firstLetter = mb_substr($customer->name, 0, 1, 'UTF-8');
        $isLocked = $customer->isLocked();
    @endphp
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            
            <!-- User Identity -->
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-3xl bg-gradient-to-tr from-primary to-rose-400 text-white font-extrabold text-2xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-primary/20 uppercase">
                    {{ $firstLetter }}
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 class="text-xl font-black text-slate-800 tracking-tight">{{ $customer->name }}</h2>
                        <span class="text-xs font-mono px-2 py-0.5 rounded-lg bg-slate-100 text-slate-500 font-bold">#{{ $customer->id }}</span>
                        
                        @if($isLocked)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-[11px] font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                <span>Tài khoản đang bị khóa</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[11px] font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>Tài khoản hoạt động</span>
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 flex-wrap">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ $customer->email }}</span>
                        </span>
                        @if($customer->phone)
                            <span class="flex items-center gap-1 font-mono">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span>{{ $customer->phone }}</span>
                            </span>
                        @endif
                        <span class="flex items-center gap-1 text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>Đăng ký: {{ $customer->created_at ? $customer->created_at->format('d/m/Y H:i') : '—' }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons Group -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Edit Profile Button -->
                <button type="button" @click="editModal = true"
                        class="px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all shadow-xs flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Sửa thông tin</span>
                </button>

                <!-- Reset Password Button -->
                <button type="button" @click="passwordModal = true"
                        class="px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-all shadow-xs flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <span>Đổi mật khẩu</span>
                </button>

                <!-- Toggle Lock Form -->
                <form method="POST" action="{{ route('admin.customers.toggle-lock', $customer) }}"
                      onsubmit="return confirm('Bạn có chắc chắn muốn {{ $isLocked ? 'mở khóa' : 'khóa' }} tài khoản khách hàng {{ $customer->name }}?');">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2.5 rounded-2xl {{ $isLocked ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-rose-600 hover:bg-rose-700 text-white' }} font-bold text-xs transition-all shadow-xs flex items-center gap-1.5 cursor-pointer">
                        @if($isLocked)
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                            </svg>
                            <span>Mở khóa tài khoản</span>
                        @else
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span>Khóa tài khoản</span>
                        @endif
                    </button>
                </form>
            </div>

        </div>
    </div>

    {{-- Statistics Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Orders -->
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tổng số đơn hàng</p>
            <div class="flex items-baseline justify-between mt-2">
                <h3 class="text-2xl font-black text-slate-800 tracking-tight">{{ number_format($stats['total_orders']) }}</h3>
                <span class="text-xs text-blue-600 font-bold bg-blue-50 px-2 py-0.5 rounded-lg">Tất cả đơn</span>
            </div>
        </div>

        <!-- Confirmed / Successful Orders -->
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Đơn thành công</p>
            <div class="flex items-baseline justify-between mt-2">
                <h3 class="text-2xl font-black text-emerald-600 tracking-tight">{{ number_format($stats['confirmed_orders']) }}</h3>
                <span class="text-xs text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-lg">Đã xác nhận</span>
            </div>
        </div>

        <!-- Total Spent -->
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tổng tiền chi tiêu</p>
            <div class="flex items-baseline justify-between mt-2">
                <h3 class="text-2xl font-black text-primary tracking-tight font-mono">{{ number_format($stats['total_spent'], 0, ',', '.') }} đ</h3>
            </div>
        </div>

        <!-- Average Order Value -->
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Giá trị đơn TB</p>
            <div class="flex items-baseline justify-between mt-2">
                <h3 class="text-2xl font-black text-slate-800 tracking-tight font-mono">{{ number_format($stats['avg_order_value'], 0, ',', '.') }} đ</h3>
                <span class="text-xs text-slate-500 font-medium">/ đơn</span>
            </div>
        </div>
    </div>

    {{-- Main Content Grid: Left Column (Addresses & Info) | Right Column (Orders History) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column: Account details & Addresses --}}
        <div class="space-y-6">

            <!-- Card: Account Detail Info -->
            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2 border-b border-slate-100 pb-3">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Thông tin tài khoản</span>
                </h3>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400 font-medium">Họ và tên:</span>
                        <span class="font-bold text-slate-800">{{ $customer->name }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400 font-medium">Email:</span>
                        <span class="font-semibold text-slate-800">{{ $customer->email }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400 font-medium">Số điện thoại:</span>
                        <span class="font-bold text-slate-800 font-mono">{{ $customer->phone ?: 'Chưa cập nhật' }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400 font-medium">Vai trò hệ thống:</span>
                        <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 font-bold uppercase text-[10px]">{{ $customer->role }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-400 font-medium">Số lần đăng nhập sai:</span>
                        <span class="font-bold {{ $customer->login_attempts >= 3 ? 'text-rose-600' : 'text-slate-700' }}">{{ $customer->login_attempts }} lần</span>
                    </div>

                    @if($isLocked)
                        <div class="flex items-center justify-between py-1 text-rose-700 font-medium bg-rose-50 p-2 rounded-xl border border-rose-200">
                            <span>Khóa đến ngày:</span>
                            <span class="font-bold">{{ $customer->locked_until->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Card: Saved Addresses List -->
            <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Sổ địa chỉ nhận hàng</span>
                    </h3>
                    <span class="text-xs font-mono font-bold text-slate-400">({{ $customer->addresses->count() }})</span>
                </div>

                <div class="space-y-3">
                    @forelse($customer->addresses as $addr)
                        <div class="p-3.5 rounded-2xl border {{ $addr->is_default ? 'border-primary/40 bg-primary/5' : 'border-slate-100 bg-slate-50/50' }} space-y-1.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">{{ $addr->receiver_name }}</span>
                                @if($addr->is_default)
                                    <span class="px-2 py-0.5 rounded-md bg-primary text-white text-[10px] font-bold">Mặc định</span>
                                @endif
                            </div>
                            <p class="text-slate-500 font-mono text-[11px]">{{ $addr->receiver_phone }}</p>
                            <p class="text-slate-700 leading-relaxed">{{ $addr->full_address }}</p>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-400 space-y-1">
                            <svg class="w-8 h-8 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                            <p class="text-xs">Khách hàng chưa lưu sổ địa chỉ nào</p>
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- Right Column: Customer Orders History --}}
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>Lịch sử đơn hàng của khách</span>
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Danh sách các đơn hàng đã đặt tại Hương Mít</p>
                    </div>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-xl bg-slate-100 text-slate-600">
                        {{ $customer->orders->count() }} đơn hàng
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3 px-6">Mã đơn</th>
                                <th class="py-3 px-4">Ngày đặt</th>
                                <th class="py-3 px-4">Sản phẩm</th>
                                <th class="py-3 px-4 text-right">Tổng tiền</th>
                                <th class="py-3 px-4 text-center">Trạng thái</th>
                                <th class="py-3 px-6 text-right">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                            @forelse($customer->orders as $order)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <!-- Order Code -->
                                    <td class="py-3.5 px-6 font-mono font-bold text-slate-900">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-primary hover:underline">
                                            #{{ $order->order_code }}
                                        </a>
                                    </td>

                                    <!-- Date -->
                                    <td class="py-3.5 px-4 text-slate-600">
                                        <div>{{ $order->created_at->format('d/m/Y') }}</div>
                                        <div class="text-[11px] text-slate-400">{{ $order->created_at->format('H:i') }}</div>
                                    </td>

                                    <!-- Products summary -->
                                    <td class="py-3.5 px-4">
                                        <div class="max-w-[220px]">
                                            @foreach($order->items->take(2) as $item)
                                                <div class="truncate text-slate-800 text-[11px]">
                                                    • {{ $item->product_name }} (x{{ $item->quantity }})
                                                </div>
                                            @endforeach
                                            @if($order->items->count() > 2)
                                                <div class="text-[10px] text-slate-400 font-semibold italic">
                                                    + {{ $order->items->count() - 2 }} sản phẩm khác
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Total amount -->
                                    <td class="py-3.5 px-4 text-right font-extrabold text-slate-900 font-mono">
                                        {{ number_format($order->total_amount, 0, ',', '.') }} đ
                                    </td>

                                    <!-- Status Badge -->
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg border text-[11px] font-bold {{ $order->status_badge_class }}">
                                            {{ $order->status_label }}
                                        </span>
                                    </td>

                                    <!-- Action -->
                                    <td class="py-3.5 px-6 text-right">
                                        <a href="{{ route('admin.orders.show', $order) }}" 
                                           class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-primary hover:text-white text-slate-700 text-xs font-bold transition-all inline-flex items-center gap-1 shadow-xs">
                                            <span>Xem đơn</span>
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center">
                                        <div class="max-w-xs mx-auto text-slate-400 space-y-1">
                                            <svg class="w-8 h-8 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                            </svg>
                                            <p class="text-xs font-semibold text-slate-600">Khách hàng chưa phát sinh đơn hàng nào</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    {{-- Modal 1: Edit Customer Info Modal --}}
    <div x-show="editModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="editModal = false"></div>

            <div class="relative inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl z-10 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900">Sửa thông tin khách hàng</h3>
                    <button type="button" @click="editModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.customers.update', $customer) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Họ và tên <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $customer->name) }}" required
                               class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $customer->email) }}" required
                               class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Số điện thoại</label>
                        <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                               class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none font-mono">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="editModal = false" 
                                class="px-4 py-2.5 rounded-2xl bg-slate-100 text-slate-600 hover:bg-slate-200 text-xs font-bold transition-colors">
                            Hủy
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 rounded-2xl bg-primary hover:bg-primary-600 text-white text-xs font-bold transition-all shadow-xs">
                            Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal 2: Reset Password Modal --}}
    <div x-show="passwordModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs" @click="passwordModal = false"></div>

            <div class="relative inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-3xl z-10 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900">Đặt lại mật khẩu cho khách</h3>
                    <button type="button" @click="passwordModal = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('admin.customers.reset-password', $customer) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mật khẩu mới <span class="text-rose-500">*</span></label>
                        <input type="password" name="password" minlength="6" placeholder="Tối thiểu 6 ký tự" required
                               class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Mật khẩu mới sẽ áp dụng ngay sau khi lưu.</p>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="passwordModal = false" 
                                class="px-4 py-2.5 rounded-2xl bg-slate-100 text-slate-600 hover:bg-slate-200 text-xs font-bold transition-colors">
                            Hủy
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition-all shadow-xs">
                            Cập nhật mật khẩu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

