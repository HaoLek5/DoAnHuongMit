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
    <div class="max-w-xl mx-auto px-4 sm:px-6">

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
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                    {{ $isDeposit ? 'Đặt cọc đơn hàng COD (40%)' : 'Thanh toán qua Ngân hàng' }}
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    @if($isDeposit)
                        Đơn hàng trên 400.000 đ chọn COD yêu cầu đặt cọc trước 40% qua VietQR.
                    @else
                        Quét mã VietQR bằng ứng dụng ngân hàng để thanh toán tự động.
                    @endif
                </p>
            </div>

            <!-- Countdown Timer Badge -->
            <template x-if="status === 'pending' && !isExpired">
                <div class="inline-flex items-center gap-2.5 px-3.5 py-2 rounded-2xl border shadow-xs transition-colors self-start sm:self-auto flex-shrink-0"
                     :class="secondsLeft <= 120 ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-amber-50 border-amber-200 text-amber-800'">
                    <div class="relative flex items-center justify-center">
                        <span class="animate-ping absolute inline-flex h-2.5 w-2.5 rounded-full opacity-75"
                              :class="secondsLeft <= 120 ? 'bg-rose-400' : 'bg-amber-400'"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2"
                              :class="secondsLeft <= 120 ? 'bg-rose-600' : 'bg-amber-500'"></span>
                    </div>
                    <div class="text-right">
                        <div class="text-[9px] uppercase font-bold tracking-wider opacity-70">Thời gian giữ mã</div>
                        <div class="text-base font-black font-mono tracking-tight" x-text="formattedTime">10:00</div>
                    </div>
                </div>
            </template>
        </div>

        @if(session('info'))
            <div class="mb-6 p-4 rounded-2xl bg-sky-50 border border-sky-200 text-xs font-semibold text-sky-800 flex items-center gap-3">
                <svg class="w-5 h-5 text-sky-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 02zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
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
            <h2 class="text-lg sm:text-xl font-bold text-rose-900">
                <template x-if="status === 'cancelled'"><span>Giao dịch chuyển khoản đã bị hủy</span></template>
                <template x-if="status !== 'cancelled'"><span>Mã QR thanh toán đã hết hạn 10 phút</span></template>
            </h2>
            <p class="text-xs text-rose-700 max-w-md mx-auto">
                Hệ thống chưa trừ tồn kho và chưa tạo đơn hàng. Giỏ hàng của bạn vẫn được giữ nguyên vẹn.
            </p>
            <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('checkout.index') }}"
                   class="px-5 py-2.5 rounded-2xl bg-primary hover:bg-primary-600 text-white font-bold text-xs shadow-md shadow-primary/20 hover:shadow-lg transition-all inline-flex items-center gap-2">
                    <span>Quay lại thanh toán</span>
                </a>
                <a href="{{ route('cart.index') }}"
                   class="px-5 py-2.5 rounded-2xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs transition-colors">
                    <span>Xem giỏ hàng</span>
                </a>
            </div>
        </div>

        <!-- MAIN PAYMENT CARD (ACTIVE STATE) -->
        <div x-show="status === 'pending' && !isExpired" class="space-y-5">

            <!-- QR Card -->
            <div class="bg-white rounded-3xl border border-primary-100/80 p-6 sm:p-8 shadow-xs text-center space-y-5 relative overflow-hidden">
                
                <!-- Amount to pay -->
                <div>
                    <span class="text-xs font-semibold text-slate-500 block mb-1">
                        {{ $isDeposit ? 'Số tiền đặt cọc cần thanh toán (40%)' : 'Số tiền thanh toán' }}
                    </span>
                    <div class="text-3xl sm:text-4xl font-black text-primary font-mono tracking-tight">
                        {{ number_format($session->amount, 0, ',', '.') }} đ
                    </div>
                    @if($isDeposit)
                        <div class="text-[11px] text-slate-500 mt-1.5 flex items-center justify-center gap-2 flex-wrap">
                            <span>Tổng đơn: <strong class="text-slate-700">{{ number_format($orderTotal, 0, ',', '.') }} đ</strong></span>
                            <span>•</span>
                            <span>Còn lại khi nhận hàng: <strong class="text-slate-700">{{ number_format($remainingAmount, 0, ',', '.') }} đ</strong></span>
                        </div>
                    @endif
                </div>

                <!-- QR Image -->
                <div class="relative mx-auto w-64 h-64 sm:w-72 sm:h-72 rounded-2xl p-3 bg-white border border-slate-200/80 shadow-inner flex items-center justify-center group">
                    <img src="{{ $session->qr_url }}" 
                         alt="Mã QR thanh toán đơn hàng Hương Mít" 
                         class="w-full h-full object-contain rounded-xl transition-transform duration-300 group-hover:scale-102">
                </div>

                <!-- Scan instructions & Transfer Content -->
                <div class="space-y-2">
                    <p class="text-xs text-slate-600">
                        Mở App Ngân hàng hoặc Ví MoMo &gt; Chọn <strong>Quét mã QR</strong>
                    </p>
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                        <span class="text-slate-400">Nội dung:</span>
                        <span class="font-mono font-bold text-slate-800">{{ $session->transfer_content }}</span>
                    </div>
                </div>

                <!-- Live Listening Animation -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-center gap-2 text-xs font-medium text-slate-500">
                    <svg class="animate-spin w-4 h-4 text-primary flex-shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Hệ thống đang tự động nhận diện thanh toán...</span>
                </div>

                {{-- Hidden metadata for test compatibility --}}
                <span class="sr-only" data-account-number="{{ $session->account_number }}">{{ $session->account_number }}</span>
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

            <!-- Order Snapshot (Collapsible) -->
            <div class="bg-white rounded-3xl border border-primary-100/80 p-5 shadow-xs space-y-3" x-data="{ openSnapshot: false }">
                <button type="button" @click="openSnapshot = !openSnapshot" class="w-full flex items-center justify-between text-xs font-bold text-slate-800">
                    <span>Chi tiết kiện hàng ({{ count($session->cart_snapshot ?? []) }} món)</span>
                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="openSnapshot ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="openSnapshot" x-cloak class="space-y-2.5 pt-2 border-t border-slate-100 text-xs">
                    @foreach($session->cart_snapshot ?? [] as $item)
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="text-slate-700 truncate">{{ $item['product_name'] }} ({{ $item['variant_name'] }}) × {{ $item['quantity'] }}</span>
                            <span class="font-bold text-slate-800 flex-shrink-0">{{ number_format($item['subtotal'], 0, ',', '.') }} đ</span>
                        </div>
                    @endforeach
                    <div class="pt-2 border-t border-slate-100 flex justify-between text-slate-500 text-[11px]">
                        <span>Người nhận:</span>
                        <span class="font-semibold text-slate-700">{{ $session->order_data['receiver_name'] ?? '' }} ({{ $session->order_data['receiver_phone'] ?? '' }})</span>
                    </div>
                    <div class="flex justify-between text-slate-500 text-[11px]">
                        <span>Địa chỉ:</span>
                        <span class="font-semibold text-slate-700 text-right truncate max-w-xs">{{ $session->order_data['shipping_address'] ?? '' }}</span>
                    </div>
                </div>
            </div>

            <!-- Simulation Button (Chỉ hiển thị trong môi trường local/testing khi route khả dụng) -->
            @if(app()->environment(['local', 'testing']) && \Illuminate\Support\Facades\Route::has('checkout.bank-payment.simulate'))
                <div class="p-4 rounded-2xl bg-purple-50/70 border border-purple-200 text-xs space-y-2">
                    <div class="flex items-center gap-2 font-bold text-purple-900">
                        <span>Chế độ thử nghiệm:</span>
                    </div>
                    <form action="{{ route('checkout.bank-payment.simulate', ['session_code' => $session->session_code]) }}" method="POST"
                          @submit="isSimulating = true">
                        @csrf
                        <button type="submit" 
                                :disabled="isSimulating"
                                class="w-full py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center justify-center gap-2 disabled:opacity-50">
                            <template x-if="!isSimulating">
                                <span>Giả lập chuyển khoản thành công ngay (Test)</span>
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

<script>
    function bankPaymentPage(initialSeconds, initialStatus, sessionCode) {
        return {
            secondsLeft: initialSeconds,
            status: initialStatus,
            sessionCode: sessionCode,
            isExpired: initialSeconds <= 0 || initialStatus === 'expired',
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
            }
        };
    }
</script>
@endsection
