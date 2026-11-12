@extends('layouts.app')

@section('title', 'Đặt hàng thành công #' . $order->order_code . ' - Hương Mít')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-10 sm:py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6">

        <!-- Main Success Card -->
        <div class="bg-white rounded-3xl border border-primary-100/80 shadow-md p-6 sm:p-10 text-center">
            
            <!-- Animated Checkmark Circle -->
            <div class="w-20 h-20 sm:w-24 sm:h-24 mx-auto rounded-full bg-emerald-50 border-4 border-emerald-100 flex items-center justify-center text-emerald-500 mb-6 shadow-inner animate-bounce">
                <svg class="w-10 h-10 sm:w-12 sm:h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Đặt hàng thành công
            </span>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Cảm ơn bạn đã tin tưởng Hương Mít!
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-lg mx-auto">
                Đơn hàng của bạn đã được ghi nhận trên hệ thống. Nhân viên tư vấn của Hương Mít sẽ liên hệ xác nhận và tiến hành đóng gói giao hàng sớm nhất.
            </p>

            <!-- Order Code Box -->
            <div class="mt-6 p-4 rounded-2xl bg-slate-50 border border-slate-200/80 inline-flex flex-col sm:flex-row items-center gap-3">
                <span class="text-xs text-slate-500">Mã đơn hàng:</span>
                <span class="text-base sm:text-lg font-black text-primary font-mono tracking-wider">{{ $order->order_code }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $order->status_badge_class }}">
                    {{ $order->status_label }}
                </span>
            </div>

            <!-- Details Card -->
            <div class="mt-8 text-left bg-slate-50/70 rounded-2xl p-5 sm:p-6 border border-slate-100 space-y-4">
                
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-200/70">
                    Thông tin đơn hàng
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block mb-0.5">Người nhận hàng:</span>
                        <span class="font-bold text-slate-800">{{ $order->receiver_name }} ({{ $order->receiver_phone }})</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block mb-0.5">Phương thức thanh toán:</span>
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @if($order->deposit_required && (float) $order->deposit_amount > 0 && (float) $order->paid_amount > 0)
                                <span class="font-bold text-amber-700 flex items-center gap-1">
                                    <span>💳</span> COD (Đã cọc 40% VietQR)
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                    Đã thanh toán cọc: {{ number_format($order->deposit_amount, 0, ',', '.') }} đ
                                </span>
                            @elseif($order->payment_method === 'bank_transfer')
                                <span class="font-bold text-primary flex items-center gap-1">
                                    <span>🏦</span> {{ $order->payment_method_label }} ({{ $order->payment_status_label }})
                                </span>
                            @else
                                <span class="font-bold text-emerald-700 flex items-center gap-1">
                                    <span>💵</span> {{ $order->payment_method_label }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <span class="text-slate-400 block mb-0.5">Địa chỉ giao hàng:</span>
                        <span class="font-semibold text-slate-700">{{ $order->shipping_address }}</span>
                    </div>

                    @if($order->note)
                    <div class="sm:col-span-2">
                        <span class="text-slate-400 block mb-0.5">Ghi chú:</span>
                        <span class="italic text-slate-600">"{{ $order->note }}"</span>
                    </div>
                    @endif
                </div>

                <!-- Products Mini List -->
                <div class="pt-3 border-t border-slate-200/70 space-y-2">
                    <span class="text-[11px] font-bold text-slate-500 block mb-2">Sản phẩm đã đặt ({{ $order->items->count() }}):</span>
                    @foreach($order->items as $item)
                        <div class="flex items-center justify-between text-xs py-1">
                            <div class="flex-1 pr-3 truncate">
                                <span class="font-bold text-slate-800">{{ $item->product_name }}</span>
                                @if($item->variant_name && $item->variant_name !== 'Mặc định')
                                    <span class="text-[11px] text-primary">({{ $item->variant_name }})</span>
                                @endif
                                <span class="text-slate-400 ml-1">x{{ $item->quantity }}</span>
                            </div>
                            <span class="font-bold text-slate-700 flex-shrink-0">
                                {{ number_format($item->subtotal, 0, ',', '.') }} đ
                            </span>
                        </div>
                    @endforeach
                </div>

                <!-- Total Amount -->
                <div class="pt-3 border-t border-slate-200/70 space-y-2 text-xs">
                    <div class="flex items-baseline justify-between">
                        <div>
                            <span class="text-slate-500">Phí vận chuyển:</span>
                            <span class="font-semibold text-slate-700 ml-1">Thanh toán khi nhận hàng</span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-slate-500 mr-2">Tổng đơn hàng:</span>
                            <span class="text-xl font-extrabold text-primary">
                                {{ number_format($order->total_amount, 0, ',', '.') }} đ
                            </span>
                        </div>
                    </div>

                    @if($order->deposit_required && (float) $order->deposit_amount > 0 && (float) $order->paid_amount > 0)
                        <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 space-y-1">
                            <div class="flex justify-between items-center text-amber-900 font-semibold">
                                <span>Tiền cọc đã thanh toán (VietQR 40%):</span>
                                <span class="font-bold text-amber-700">-{{ number_format($order->deposit_amount, 0, ',', '.') }} đ</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-800 pt-1 border-t border-amber-200 font-bold">
                                <span>Tiền shipper sẽ thu khi giao hàng (60% COD):</span>
                                <span class="text-sm font-extrabold text-slate-900">{{ number_format($order->remaining_amount, 0, ',', '.') }} đ</span>
                            </div>
                        </div>
                    @endif
                </div>

            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('account.orders.show', $order->order_code) }}"
                   class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-xs shadow-md shadow-primary/25 transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span>Theo dõi đơn hàng</span>
                </a>

                <a href="{{ route('home') }}"
                   class="w-full sm:w-auto px-6 py-3.5 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition-all flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Tiếp tục mua sắm</span>
                </a>
            </div>

        </div>

    </div>
</div>
@endsection
