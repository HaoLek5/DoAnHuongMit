@php
    $isDeposit = !empty($session->order_data['is_deposit']) || !empty($session->order_data['deposit_required']);
    $orderTotal = (float) ($session->order_data['total_amount'] ?? $session->amount);
    $depositAmount = (float) $session->amount;
    $remainingAmount = $isDeposit ? max(0, $orderTotal - $depositAmount) : 0;
@endphp

@extends('layouts.app')

@section('title', ($isDeposit ? 'Đặt cọc đơn hàng COD' : 'Thanh toán chuyển khoản VietQR') . ' - Hương Mít')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-8 sm:py-12" x-data="bankPaymentPage({{ $remainingSeconds }}, '{{ $session->status }}', '{{ $session->session_code }}')" x-init="init()">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('cart.index') }}" class="hover:text-primary transition-colors">Giỏ hàng</a>
            <span>/</span>
            <a href="{{ route('checkout.index') }}" class="hover:text-primary transition-colors">Thanh toán</a>
            <span>/</span>
            <span class="text-primary font-bold">{{ $isDeposit ? 'Đặt cọc 40% VietQR' : 'Chuyển khoản VietQR' }}</span>
        </nav>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-2xl bg-primary-50 text-primary flex items-center justify-center font-bold text-lg">{{ $isDeposit ? '💳' : '🏦' }}</span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        {{ $isDeposit ? 'Đặt cọc đơn hàng COD (40%)' : 'Thanh toán qua Ngân hàng' }}
                    </h1>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    @if($isDeposit)
                        Đơn hàng trên 400.000 đ chọn COD yêu cầu đặt cọc trước 40% qua VietQR. Khi nhận hàng, bạn chỉ thanh toán 60% còn lại.
                    @else
                        Quét mã VietQR bằng ứng dụng ngân hàng bất kỳ để hoàn tất đơn hàng tự động.
                    @endif
                </p>
            </div>

            <!-- Countdown Timer Badge -->
            <template x-if="status === 'pending' && !isExpired">
                <div class="inline-flex items-center gap-3 px-4 py-2.5 rounded-2xl border shadow-xs transition-colors"
                     :class="secondsLeft <= 120 ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-amber-50 border-amber-200 text-amber-800'">
                    <div class="relative flex items-center justify-center">
                        <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full opacity-75"
                              :class="secondsLeft <= 120 ? 'bg-rose-400' : 'bg-amber-400'"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2"
                              :class="secondsLeft <= 120 ? 'bg-rose-600' : 'bg-amber-500'"></span>
                    </div>
                    <div>
                        <div class="text-[10px] uppercase font-bold tracking-wider opacity-70">Thời gian giữ mã QR</div>
                        <div class="text-lg font-black font-mono tracking-tight" x-text="formattedTime">10:00</div>
                    </div>
                </div>
            </template>
        </div>

        @if(session('info'))
            <div class="mb-6 p-4 rounded-2xl bg-sky-50 border border-sky-200 text-xs font-semibold text-sky-800 flex items-center gap-3">
                <svg class="w-5 h-5 text-sky-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- EXPIRED OR CANCELLED STATE BANNER -->
        <div x-show="status === 'expired' || status === 'cancelled' || isExpired" x-cloak
             class="mb-8 p-6 sm:p-8 rounded-3xl bg-rose-50 border-2 border-rose-200 text-center space-y-3 shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-rose-100 text-rose-600 mx-auto flex items-center justify-center text-2xl font-bold">
                ⏰
            </div>
            <h2 class="text-lg sm:text-xl font-bold text-rose-900">
                <template x-if="status === 'cancelled'"><span>Giao dịch chuyển khoản đã bị hủy</span></template>
                <template x-if="status !== 'cancelled'"><span>Mã QR thanh toán đã hết hạn 10 phút</span></template>
            </h2>
            <p class="text-xs sm:text-sm text-rose-700 max-w-lg mx-auto">
                Hệ thống chưa trừ tồn kho và chưa tạo đơn hàng. Giỏ hàng của bạn vẫn được giữ nguyên vẹn. Bạn có thể tạo lại mã thanh toán mới hoặc chọn phương thức COD.
            </p>
            <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('checkout.index') }}"
                   class="px-6 py-3 rounded-2xl bg-primary hover:bg-primary-600 text-white font-bold text-xs shadow-md shadow-primary/20 hover:shadow-lg transition-all inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Quay lại trang thanh toán</span>
                </a>
                <a href="{{ route('cart.index') }}"
                   class="px-6 py-3 rounded-2xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs transition-colors">
                    <span>Xem lại giỏ hàng</span>
                </a>
            </div>
        </div>

        <!-- MAIN PAYMENT GRID (ACTIVE STATE) -->
        <div x-show="status === 'pending' && !isExpired" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Left Column: QR Code & Status (5 Cols) -->
            <div class="lg:col-span-5 space-y-4">
                
                <div class="bg-white rounded-3xl border border-primary-100 p-6 sm:p-7 shadow-sm text-center relative overflow-hidden">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-50 text-primary text-[11px] font-bold mb-4">
                        <span>⚡ Chuẩn VietQR NAPAS 247</span>
                    </div>

                    <!-- QR Image Container -->
                    <div class="relative mx-auto w-64 h-64 sm:w-72 sm:h-72 rounded-2xl p-2.5 bg-white border border-slate-200/80 shadow-inner flex items-center justify-center group">
                        <img src="{{ $session->qr_url }}" 
                             alt="Mã QR thanh toán đơn hàng Hương Mít" 
                             class="w-full h-full object-contain rounded-xl transition-transform duration-300 group-hover:scale-102">
                    </div>

                    <!-- Scan instructions -->
                    <div class="mt-4 space-y-1.5">
                        <p class="text-xs font-bold text-slate-800">
                            Mở ứng dụng Ngân hàng hoặc Ví MoMo
                        </p>
                        <p class="text-[11px] text-slate-500 leading-relaxed max-w-xs mx-auto">
                            Chọn tính năng <strong class="text-slate-700">Quét mã QR</strong> trên App ngân hàng để hệ thống tự động điền số tài khoản, số tiền và nội dung.
                        </p>
                    </div>

                    <!-- Live Listening Animation -->
                    <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-center gap-2 text-xs font-medium text-slate-600 bg-slate-50/70 -mx-6 -mb-6 p-4">
                        <svg class="animate-spin w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Hệ thống đang tự động nhận diện thanh toán...</span>
                    </div>
                </div>

                <!-- Cancel Button -->
                <form action="{{ route('checkout.bank-payment.cancel', ['session_code' => $session->session_code]) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc chắn muốn hủy mã QR này và đổi sang phương thức thanh toán khác?')">
                    @csrf
                    <button type="submit" 
                            class="w-full py-3 rounded-2xl bg-white hover:bg-rose-50 border border-slate-200 hover:border-rose-200 text-slate-600 hover:text-rose-600 font-bold text-xs transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Hủy mã QR & Đổi phương thức thanh toán</span>
                    </button>
                </form>

            </div>

            <!-- Right Column: Transfer Info & Details (7 Cols) -->
            <div class="lg:col-span-7 space-y-5">
                
                <!-- Bank Info Card with 1-click Copy -->
                <div class="bg-white rounded-3xl border border-primary-100 p-6 sm:p-7 shadow-sm space-y-5">
                    
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h2 class="text-base font-bold text-slate-800">Thông tin chuyển khoản thủ công</h2>
                            <p class="text-[11px] text-slate-400 mt-0.5">Nếu không quét được mã QR, bạn có thể chuyển khoản bằng thông tin dưới đây</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            24/7 Miễn phí
                        </span>
                    </div>

                    <div class="space-y-3.5 text-xs">
                        
                        <!-- Ngân hàng -->
                        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                            <div>
                                <span class="text-slate-400 text-[11px] block">Ngân hàng thụ hưởng</span>
                                <span class="font-bold text-slate-800 text-sm">{{ $session->bank_name }}</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl bg-white border border-slate-200 font-mono font-bold text-xs text-primary">
                                {{ $session->bank_id }}
                            </span>
                        </div>

                        <!-- Số tài khoản -->
                        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                            <div>
                                <span class="text-slate-400 text-[11px] block">Số tài khoản</span>
                                <span class="font-bold text-slate-900 text-base font-mono tracking-wider">{{ $session->account_number }}</span>
                            </div>
                            <button type="button" @click="copyToClipboard('{{ $session->account_number }}', 'stk')"
                                    class="px-3 py-1.5 rounded-xl bg-white hover:bg-primary-50 border border-slate-200 hover:border-primary/30 text-primary font-bold text-xs transition-colors flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span x-text="copiedField === 'stk' ? 'Đã chép!' : 'Sao chép'">Sao chép</span>
                            </button>
                        </div>

                        <!-- Chủ tài khoản -->
                        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                            <div>
                                <span class="text-slate-400 text-[11px] block">Chủ tài khoản</span>
                                <span class="font-bold text-slate-800 text-sm uppercase">{{ $session->account_name }}</span>
                            </div>
                        </div>

                        @if($isDeposit)
                            <!-- Tóm tắt cọc & COD -->
                            <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200 space-y-1.5 text-xs text-amber-900">
                                <div class="flex justify-between items-center text-slate-700">
                                    <span>Tổng giá trị đơn hàng:</span>
                                    <span class="font-bold text-slate-900">{{ number_format($orderTotal, 0, ',', '.') }} đ</span>
                                </div>
                                <div class="flex justify-between items-center text-amber-800 font-semibold">
                                    <span>Tiền cọc cần chuyển (40%):</span>
                                    <span class="font-extrabold text-amber-700 text-sm">{{ number_format($depositAmount, 0, ',', '.') }} đ</span>
                                </div>
                                <div class="flex justify-between items-center text-slate-700 pt-1.5 border-t border-amber-200">
                                    <span>Còn lại thanh toán khi nhận hàng (60% COD):</span>
                                    <span class="font-bold text-slate-900">{{ number_format($remainingAmount, 0, ',', '.') }} đ</span>
                                </div>
                            </div>
                        @endif

                        <!-- Số tiền cần thanh toán -->
                        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-primary-50/40 border border-primary-200">
                            <div>
                                <span class="text-primary-800 text-[11px] font-semibold block">
                                    {{ $isDeposit ? 'Số tiền đặt cọc cần chuyển (40%)' : 'Số tiền cần thanh toán chính xác' }}
                                </span>
                                <span class="font-black text-primary text-xl font-mono">{{ number_format($session->amount, 0, ',', '.') }} đ</span>
                            </div>
                            <button type="button" @click="copyToClipboard('{{ (int) round($session->amount) }}', 'amount')"
                                    class="px-3 py-1.5 rounded-xl bg-white hover:bg-primary-50 border border-primary-200 text-primary font-bold text-xs transition-colors flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span x-text="copiedField === 'amount' ? 'Đã chép!' : 'Sao chép'">Sao chép</span>
                            </button>
                        </div>

                        <!-- Nội dung chuyển khoản (Bắt buộc giữ nguyên) -->
                        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-amber-50/50 border-2 border-amber-300">
                            <div>
                                <span class="text-amber-800 text-[11px] font-bold block">Nội dung chuyển khoản (Bắt buộc ghi đúng)</span>
                                <span class="font-black text-amber-900 text-base font-mono tracking-wider">{{ $session->transfer_content }}</span>
                            </div>
                            <button type="button" @click="copyToClipboard('{{ $session->transfer_content }}', 'content')"
                                    class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span x-text="copiedField === 'content' ? 'Đã chép!' : 'Sao chép'">Sao chép</span>
                            </button>
                        </div>

                    </div>

                    <!-- Important Note Callout -->
                    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200/80 text-xs text-amber-900 leading-relaxed flex items-start gap-2.5">
                        <span class="text-base flex-shrink-0">⚠️</span>
                        <div>
                            <strong class="font-bold">Lưu ý quan trọng:</strong>
                            <p class="mt-0.5 text-amber-800 text-[11px]">
                                @if($isDeposit)
                                    Vui lòng giữ nguyên nội dung chuyển khoản <strong class="font-mono font-bold">{{ $session->transfer_content }}</strong>. Sau khi cọc thành công, đơn hàng sẽ được xác nhận tự động. Khi nhận hàng, bạn chỉ cần thanh toán số tiền còn lại là <strong class="text-slate-900 font-bold">{{ number_format($remainingAmount, 0, ',', '.') }} đ</strong> cho shipper.
                                @else
                                    Vui lòng giữ nguyên nội dung chuyển khoản <strong class="font-mono font-bold">{{ $session->transfer_content }}</strong> để hệ thống tự động đối soát, trừ tồn kho và xác nhận đơn hàng ngay khi tiền vào tài khoản.
                                @endif
                            </p>
                        </div>
                    </div>

                </div>

                <!-- Order items snapshot -->
                <div class="bg-white rounded-3xl border border-primary-100 p-6 sm:p-7 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 text-xs font-bold text-slate-800">
                        <span>Chi tiết kiện hàng</span>
                        <span class="text-slate-400 font-semibold">{{ count($session->cart_snapshot ?? []) }} món</span>
                    </div>

                    <div class="space-y-3 max-h-56 overflow-y-auto pr-1">
                        @foreach($session->cart_snapshot ?? [] as $item)
                            <div class="flex items-center justify-between gap-3 text-xs pb-2 border-b border-slate-50 last:border-b-0">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                        @if(!empty($item['image_url']))
                                            <img src="{{ $item['image_url'] }}" alt="{{ $item['product_name'] }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-[10px] text-slate-400">Ảnh</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 truncate">
                                        <p class="font-bold text-slate-800 truncate">{{ $item['product_name'] }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $item['variant_name'] }} × {{ $item['quantity'] }}</p>
                                    </div>
                                </div>
                                <span class="font-bold text-slate-800 flex-shrink-0">
                                    {{ number_format($item['subtotal'], 0, ',', '.') }} đ
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-3 border-t border-slate-100 text-xs text-slate-500 space-y-1">
                        <div class="flex justify-between">
                            <span>Người nhận:</span>
                            <span class="font-semibold text-slate-800">{{ $session->order_data['receiver_name'] ?? '' }} ({{ $session->order_data['receiver_phone'] ?? '' }})</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Địa chỉ giao:</span>
                            <span class="font-semibold text-slate-800 text-right max-w-xs truncate">{{ $session->order_data['shipping_address'] ?? '' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Simulation Button (Chỉ hiển thị trong môi trường local/testing khi route khả dụng) -->
                @if(app()->environment(['local', 'testing']) && \Illuminate\Support\Facades\Route::has('checkout.bank-payment.simulate'))
                    <div class="p-4 rounded-2xl bg-purple-50/70 border border-purple-200 text-xs space-y-2">
                        <div class="flex items-center gap-2 font-bold text-purple-900">
                            <span>🧪</span>
                            <span>Chế độ thử nghiệm (Dành cho kiểm tra thực tế trên Shop):</span>
                        </div>
                        <p class="text-purple-700 text-[11px] leading-relaxed">
                            Bạn có thể bấm nút bên dưới để giả lập ngân hàng gửi thông báo đã chuyển khoản thành công. Hệ thống sẽ ngay lập tức đối soát, trừ tồn kho và tạo đơn hàng.
                        </p>
                        <form action="{{ route('checkout.bank-payment.simulate', ['session_code' => $session->session_code]) }}" method="POST"
                              @submit="isSimulating = true">
                            @csrf
                            <button type="submit" 
                                    :disabled="isSimulating"
                                    class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center justify-center gap-2 disabled:opacity-50">
                                <template x-if="!isSimulating">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <span>Giả lập chuyển khoản thành công ngay (Test)</span>
                                    </div>
                                </template>
                                <template x-if="isSimulating">
                                    <span>Đang xác nhận đơn hàng...</span>
                                </template>
                            </button>
                        </form>
                    </div>
                @endif

            </div>

        </div>

    </div>
</div>

<script>
    function bankPaymentPage(initialSeconds, initialStatus, sessionCode) {
        return {
            secondsLeft: initialSeconds,
            status: initialStatus,
            sessionCode: sessionCode,
            isExpired: initialSeconds <= 0 || initialStatus === 'expired',
            copiedField: '',
            isSimulating: false,
            pollInterval: null,
            timerInterval: null,

            init() {
                if (this.status !== 'pending' || this.isExpired) {
                    return;
                }

                // 1. Đồng hồ đếm ngược mỗi giây
                this.timerInterval = setInterval(() => {
                    if (this.secondsLeft > 0) {
                        this.secondsLeft--;
                    } else {
                        this.isExpired = true;
                        this.status = 'expired';
                        clearInterval(this.timerInterval);
                        if (this.pollInterval) clearInterval(this.pollInterval);
                    }
                }, 1000);

                // 2. Polling kiểm tra trạng thái thanh toán từ máy chủ mỗi 3 giây
                this.pollInterval = setInterval(() => {
                    this.checkStatus();
                }, 3000);
            },

            get formattedTime() {
                const minutes = Math.floor(this.secondsLeft / 60);
                const seconds = this.secondsLeft % 60;
                return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            },

            async checkStatus() {
                if (this.isExpired || this.status !== 'pending') return;

                try {
                    const response = await fetch(`/thanh-toan/chuyen-khoan/${this.sessionCode}/check-status`, {
                        headers: {
                            'Accept': 'application/json',
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            if (data.status === 'paid' && data.redirect_url) {
                                if (this.pollInterval) clearInterval(this.pollInterval);
                                if (this.timerInterval) clearInterval(this.timerInterval);
                                window.location.href = data.redirect_url;
                            } else if (data.status === 'expired' || data.is_expired) {
                                this.status = 'expired';
                                this.isExpired = true;
                                if (this.pollInterval) clearInterval(this.pollInterval);
                                if (this.timerInterval) clearInterval(this.timerInterval);
                            } else if (data.status === 'cancelled') {
                                this.status = 'cancelled';
                                if (this.pollInterval) clearInterval(this.pollInterval);
                                if (this.timerInterval) clearInterval(this.timerInterval);
                            }
                        }
                    }
                } catch (e) {
                    console.log('Polling check error:', e);
                }
            },

            copyToClipboard(text, fieldName) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => {
                        this.copiedField = fieldName;
                        setTimeout(() => { this.copiedField = ''; }, 2000);
                    });
                } else {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    this.copiedField = fieldName;
                    setTimeout(() => { this.copiedField = ''; }, 2000);
                }
            }
        };
    }
</script>
@endsection

