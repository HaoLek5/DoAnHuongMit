@extends('layouts.app')

@section('title', 'Đặt hàng thành công #' . $order->order_code . ' - Hương Mít')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-12 sm:py-20 flex items-center justify-center">
    <div class="w-full max-w-lg mx-auto px-4 sm:px-6">

        <!-- Main Success Card -->
        <div class="bg-white rounded-3xl border border-primary-100/80 shadow-xs p-8 sm:p-10 text-center space-y-6">
            
            <!-- Checkmark Circle -->
            <div class="w-20 h-20 mx-auto rounded-full bg-emerald-50 border-4 border-emerald-100 flex items-center justify-center text-emerald-500 shadow-inner">
                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <!-- Header & Notification -->
            <div class="space-y-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Đặt hàng thành công
                </span>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight pt-1">
                    Cảm ơn bạn đã đặt hàng!
                </h1>
                
                <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto leading-relaxed">
                    Đơn hàng của bạn đã được ghi nhận trên hệ thống. Chúng tôi sẽ liên hệ xác nhận và tiến hành giao hàng sớm nhất.
                </p>
            </div>

            <!-- Order Code Box -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 inline-flex flex-wrap items-center justify-center gap-2.5 text-xs">
                <span class="text-slate-400 font-medium">Mã đơn hàng:</span>
                <span class="font-black text-primary font-mono text-sm tracking-wider">{{ $order->order_code }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $order->status_badge_class }}">
                    {{ $order->status_label }}
                </span>
            </div>

            <!-- Action Buttons -->
            <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('account.orders.show', $order->order_code) }}"
                   class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-primary hover:bg-primary-600 text-white font-bold text-xs shadow-md shadow-primary/20 hover:shadow-lg transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span>Chi tiết đơn hàng</span>
                </a>

                <a href="{{ route('home') }}"
                   class="w-full sm:w-auto px-6 py-3 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Tiếp tục mua sắm</span>
                </a>
            </div>

        </div>

    </div>
</div>
@endsection
