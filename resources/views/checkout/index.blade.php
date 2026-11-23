@extends('layouts.app')

@section('title', 'Thanh toán đơn hàng - Hương Mít')

@section('content')
<div class="bg-[#FAF8F5] min-h-screen py-8 sm:py-12" x-data="checkoutPage()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs text-slate-500 mb-6">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('cart.index') }}" class="hover:text-primary transition-colors">Giỏ hàng</a>
            <span>/</span>
            <span class="text-primary font-bold">Thanh toán</span>
        </nav>

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Thanh toán đơn hàng</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Vui lòng kiểm tra địa chỉ nhận hàng và thông tin thanh toán trước khi xác nhận</p>
            </div>
            <a href="{{ route('cart.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-primary hover:text-primary-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Quay lại giỏ hàng</span>
            </a>
        </div>

        @if(session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700 space-y-1">
                <div class="font-bold">Vui lòng kiểm tra lại thông tin:</div>
                <ul class="list-disc list-inside space-y-0.5 text-rose-600">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('checkout.process') }}" method="POST" @submit="handleSubmit($event)" id="checkout-form">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

            <!-- Hidden input to submit selected address -->
            <input type="hidden" name="address_type" value="existing">
            <input type="hidden" name="address_id" :value="selectedAddressId">
            <input type="hidden" name="items" value="{{ request('items') }}">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                <!-- Left Column: Shipping & Payment (7 Cols) -->
                <div class="lg:col-span-7 space-y-6">

                    <!-- 1. THÔNG TIN NHẬN HÀNG -->
                    <div class="bg-white rounded-3xl border border-primary-100 p-5 sm:p-6 shadow-xs">
                        <div class="flex items-center justify-between mb-3 pb-3 border-b border-slate-100">
                            <h2 class="text-base font-bold text-slate-800">Thông tin nhận hàng</h2>

                            <div>
                                <template x-if="selectedAddress && addresses.length > 1">
                                    <button type="button" @click="openSelectModal()"
                                            class="text-xs font-bold text-primary hover:text-primary-700 transition-colors">
                                        Đổi địa chỉ
                                    </button>
                                </template>
                                <template x-if="!selectedAddress || addresses.length <= 1">
                                    <button type="button" @click="openAddressModal()"
                                            class="text-xs font-bold text-primary hover:text-primary-700 transition-colors">
                                        + Thêm địa chỉ
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- TRƯỜNG HỢP 1: ĐÃ CÓ ĐỊA CHỈ ĐƯỢC CHỌN -->
                        <template x-if="selectedAddress">
                            <div class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50/50 text-xs space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-extrabold text-slate-900" x-text="selectedAddress.receiver_name"></span>
                                    <span class="text-slate-300">|</span>
                                    <span class="font-bold text-slate-700" x-text="selectedAddress.receiver_phone"></span>
                                    <template x-if="selectedAddress.is_default">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-primary/10 text-primary">
                                            Mặc định
                                        </span>
                                    </template>
                                </div>
                                <p class="text-slate-600 leading-normal" x-text="formatFullAddress(selectedAddress)"></p>
                            </div>
                        </template>

                        <!-- TRƯỜNG HỢP 2: CHƯA CÓ ĐỊA CHỈ NÀO -->
                        <template x-if="!selectedAddress">
                            <div class="py-4 text-center">
                                <p class="text-xs text-slate-500 mb-2">Chưa có thông tin địa chỉ nhận hàng.</p>
                                <button type="button" @click="openAddressModal()"
                                        class="px-4 py-2 rounded-xl bg-primary text-white font-bold text-xs hover:bg-primary-600 transition-colors">
                                    + Thêm địa chỉ nhận hàng
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- 2. PHƯƠNG THỨC THANH TOÁN -->
                    <div class="bg-white rounded-3xl border border-primary-100 p-5 sm:p-6 shadow-xs">
                        <h2 class="text-base font-bold text-slate-800 mb-3 pb-3 border-b border-slate-100">Phương thức thanh toán</h2>

                        <div class="space-y-2.5">
                            <!-- 1. Contact Shop Option (Mới, Hoạt động) -->
                            <label class="flex items-center justify-between p-3.5 sm:p-4 rounded-2xl border cursor-pointer transition-all"
                                   :class="paymentMethod === 'contact_shop' ? 'border-primary bg-primary-50/15' : 'border-slate-200/80 hover:border-slate-300 bg-white'">
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="payment_method" value="contact_shop" x-model="paymentMethod"
                                           class="text-primary focus:ring-primary h-4 w-4 border-slate-300">
                                    <div>
                                        <span class="text-xs sm:text-sm font-bold text-slate-800">Liên hệ shop</span>
                                        <p class="text-[11px] text-slate-500 font-normal mt-0.5">Shop sẽ liên hệ với bạn để tư vấn, xác nhận đơn hàng và hướng dẫn giao nhận</p>
                                    </div>
                                </div>
                                <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-200">
                                    Miễn phí cọc
                                </span>
                            </label>

                            <!-- 2. COD Option (Tạm khóa) -->
                            <div class="flex items-center justify-between p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 bg-slate-50/70 opacity-60 cursor-not-allowed">
                                <div class="flex items-center gap-3">
                                    <input type="radio" disabled class="text-slate-400 h-4 w-4 border-slate-300 cursor-not-allowed">
                                    <div>
                                        <span class="text-xs sm:text-sm font-bold text-slate-500">Thanh toán khi nhận hàng (COD)</span>
                                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Phương thức này đang tạm khóa, vui lòng chọn Liên hệ shop</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-500 bg-slate-200/70 px-2 py-0.5 rounded-lg border border-slate-300/60">
                                    Tạm khóa
                                </span>
                            </div>

                            <!-- 3. Bank Transfer (VietQR) Option (Tạm khóa) -->
                            <div class="flex items-center justify-between p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 bg-slate-50/70 opacity-60 cursor-not-allowed">
                                <div class="flex items-center gap-3">
                                    <input type="radio" disabled class="text-slate-400 h-4 w-4 border-slate-300 cursor-not-allowed">
                                    <div>
                                        <span class="text-xs sm:text-sm font-bold text-slate-500">Chuyển khoản ngân hàng (VietQR)</span>
                                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Phương thức này đang tạm khóa, vui lòng chọn Liên hệ shop</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-slate-500 bg-slate-200/70 px-2 py-0.5 rounded-lg border border-slate-300/60">
                                    Tạm khóa
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 4. GHI CHÚ ĐƠN HÀNG -->
                    <div class="bg-white rounded-3xl border border-primary-100 p-6 sm:p-7 shadow-xs">
                        <label class="block text-xs font-bold text-slate-700 mb-2">
                            Ghi chú đơn hàng (Tùy chọn)
                        </label>
                        <textarea name="note" rows="2"
                                  placeholder="Ví dụ: Giao hàng vào giờ hành chính, gọi trước khi đến 15 phút..."
                                  class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none resize-none">{{ old('note') }}</textarea>
                    </div>

                </div>

                <!-- Right Column: Order Summary (5 Cols) -->
                <div class="lg:col-span-5 sticky top-28 space-y-5">
                    
                    <div class="bg-white rounded-3xl border border-primary-100 p-6 sm:p-7 shadow-sm space-y-5">
                        
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <h3 class="text-base font-bold text-slate-800">Tóm tắt đơn hàng</h3>
                            <span class="text-xs font-semibold text-slate-500">{{ $cart->total_quantity }} sản phẩm</span>
                        </div>

                        <!-- Items List -->
                        <div class="space-y-3.5 max-h-72 overflow-y-auto pr-1">
                            @foreach($cart->items as $item)
                                <div class="flex items-center gap-3 pb-3 border-b border-slate-100/70 last:border-b-0 last:pb-0">
                                    <div class="w-14 h-14 rounded-2xl border border-slate-100 bg-slate-50 overflow-hidden flex-shrink-0 flex items-center justify-center p-1">
                                        @if($item->product->primaryImage)
                                            <img src="{{ $item->product->primaryImage->image_url }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover rounded-xl">
                                        @else
                                            <span class="text-xs text-slate-400">Ảnh</span>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-bold text-slate-800 truncate">{{ $item->product->name }}</h4>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            @if($item->variant && $item->variant->variant_title !== 'Mặc định')
                                                <span class="text-[11px] font-semibold text-primary bg-primary-50 px-2 py-0.5 rounded-md">
                                                    {{ $item->variant->variant_title }}
                                                </span>
                                            @endif
                                            <span class="text-[11px] text-slate-400 font-medium">SL: {{ $item->quantity }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-bold text-slate-800">
                                            {{ number_format($item->quantity * $item->price, 0, ',', '.') }} đ
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Price Breakdown -->
                        <div class="pt-2 space-y-2.5 text-xs border-t border-slate-100">
                            <div class="flex justify-between text-slate-500">
                                <span>Tạm tính</span>
                                <span class="font-bold text-slate-800">{{ number_format($cart->total_price, 0, ',', '.') }} đ</span>
                            </div>
                            <div class="flex justify-between text-slate-500">
                                <span>Phí vận chuyển</span>
                                <span class="font-semibold text-slate-700">Thanh toán khi nhận hàng</span>
                            </div>

                            <template x-if="isCodWithDeposit">
                                <div class="space-y-2 pt-2 border-t border-dashed border-amber-200">
                                    <div class="flex justify-between items-center text-xs p-2.5 rounded-xl bg-amber-50 border border-amber-200">
                                        <div>
                                            <span class="font-bold text-amber-900 block">Tiền cọc cần chuyển (40%):</span>
                                            <span class="text-[10px] text-amber-700">Quét mã VietQR sau bước này</span>
                                        </div>
                                        <span class="font-extrabold text-amber-700 text-sm" x-text="formatCurrency(depositAmount)"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                                        <div>
                                            <span class="font-bold text-slate-700 block">Thu khi nhận hàng (60% COD):</span>
                                            <span class="text-[10px] text-slate-500">Thanh toán tiền mặt cho shipper</span>
                                        </div>
                                        <span class="font-bold text-slate-900 text-sm" x-text="formatCurrency(remainingAmount)"></span>
                                    </div>
                                </div>
                            </template>
                            
                            <div class="border-t border-dashed border-slate-200 pt-3 flex justify-between items-baseline">
                                <span class="text-sm font-bold text-slate-800">Tổng đơn hàng:</span>
                                <div class="text-right">
                                    <span class="text-2xl font-extrabold text-primary">
                                        {{ number_format($cart->total_price, 0, ',', '.') }} đ
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" 
                                    :disabled="isSubmitting"
                                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-extrabold text-sm shadow-md shadow-primary/30 hover:shadow-xl hover:scale-[1.01] active:scale-[0.98] transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!isSubmitting">
                                    <div class="flex items-center gap-2">
                                        <template x-if="paymentMethod === 'contact_shop'">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span>Xác nhận đặt hàng (Liên hệ shop)</span>
                                            </div>
                                        </template>
                                        <template x-if="paymentMethod === 'cod'">
                                            <div class="flex items-center gap-2">
                                                <template x-if="totalPrice > 400000">
                                                    <div class="flex items-center gap-2">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                                        </svg>
                                                        <span>Quét mã QR đặt cọc 40% (<span x-text="formatCurrency(depositAmount)"></span>)</span>
                                                    </div>
                                                </template>
                                                <template x-if="totalPrice <= 400000">
                                                    <div class="flex items-center gap-2">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                        <span>Xác nhận đặt hàng (COD)</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="paymentMethod === 'bank_transfer'">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                                </svg>
                                                <span>Tiến hành quét mã QR thanh toán 100%</span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="isSubmitting">
                                    <div class="flex items-center gap-2">
                                        <svg class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Đang chuyển hướng...</span>
                                    </div>
                                </template>
                            </button>
                        </div>

                    </div>

                </div>

            </div>

        </form>

    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: FORM THÊM ĐỊA CHỈ GIAO HÀNG MỚI (CHUẨN 100% THEO FORM SẴN)     -->
    <!-- ========================================================================= -->
    <div x-show="showAddressModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div x-show="showAddressModal"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showAddressModal = false"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="showAddressModal"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-lg border border-slate-100">

                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h3 class="font-bold text-base text-slate-800">Thêm địa chỉ giao hàng mới</h3>
                    <button type="button" @click="showAddressModal = false"
                            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Error notice inside modal if validation fails -->
                <template x-if="formError">
                    <div class="mx-6 mt-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        <span x-text="formError"></span>
                    </div>
                </template>

                <!-- Modal Form -->
                <div class="p-6 space-y-4">
                    <!-- Row 1: Receiver Name & Phone -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Họ tên người nhận <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" x-model="form.receiver_name" placeholder="vd: Nguyễn Văn A" required
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Số điện thoại <span class="text-rose-500">*</span>
                            </label>
                            <input type="tel" x-model="form.receiver_phone" placeholder="vd: 0987654321" required
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        </div>
                    </div>

                    <!-- Row 2: Province -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Tỉnh / Thành phố <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="form.province" required
                                class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none bg-white">
                            <option value="">-- Chọn Tỉnh / Thành phố --</option>
                            @foreach($provinces as $prov)
                                <option value="{{ $prov }}">{{ $prov }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Row 3: District & Ward -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Quận / Huyện
                            </label>
                            <input type="text" x-model="form.district" placeholder="vd: Quận Cầu Giấy, Huyện Thanh Trì..."
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">
                                Phường / Xã
                            </label>
                            <input type="text" x-model="form.ward" placeholder="vd: Phường Dịch Vọng Hậu..."
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                        </div>
                    </div>

                    <!-- Row 4: Detail Address -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Địa chỉ cụ thể (Số nhà, ngõ, tên đường) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" x-model="form.detail_address" placeholder="vd: Số 25 ngõ 180 đường Trần Duy Hưng" required
                               class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                    </div>

                    <!-- Default Checkbox -->
                    <div class="pt-2 flex items-center gap-2.5">
                        <input type="checkbox" id="chk_modal_default" x-model="form.is_default"
                               class="rounded border-slate-300 text-primary focus:ring-primary/20">
                        <label for="chk_modal_default" class="text-xs font-bold text-slate-700 cursor-pointer select-none">
                            Đặt làm địa chỉ nhận hàng mặc định
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                        <button type="button" @click="showAddressModal = false"
                                class="px-5 py-2.5 rounded-2xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-bold transition-all">
                            Hủy bỏ
                        </button>
                        <button type="button" @click="saveAddress()" :disabled="isSavingAddress"
                                class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-xs shadow-md shadow-primary/25 hover:shadow-lg transition-all flex items-center gap-1.5 disabled:opacity-50">
                            <template x-if="!isSavingAddress">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span>Lưu địa chỉ</span>
                                </span>
                            </template>
                            <template x-if="isSavingAddress">
                                <span class="flex items-center gap-1.5">
                                    <svg class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>Đang lưu...</span>
                                </span>
                            </template>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: CHỌN ĐỊA CHỈ TỪ SỔ ĐỊA CHỈ (KHI NGƯỜI DÙNG CÓ NHIỀU ĐỊA CHỈ)   -->
    <!-- ========================================================================= -->
    <div x-show="showSelectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showSelectModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-lg border border-slate-100 p-6 sm:p-7 space-y-5" @click.stop>
                
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Chọn địa chỉ nhận hàng</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Chọn một địa chỉ từ sổ địa chỉ của bạn</p>
                    </div>
                    <button type="button" @click="showSelectModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Addresses List in Modal -->
                <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                    <template x-for="addr in addresses" :key="addr.id">
                        <div @click="tempSelectedAddressId = addr.id"
                             class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex items-start gap-3.5"
                             :class="tempSelectedAddressId == addr.id ? 'border-primary bg-primary-50/20 ring-2 ring-primary/10' : 'border-slate-200/80 hover:border-slate-300 bg-white'">
                            
                            <input type="radio" :value="addr.id" :checked="tempSelectedAddressId == addr.id"
                                   class="mt-1 text-primary focus:ring-primary h-4 w-4 border-slate-300">
                            
                            <div class="flex-1 text-xs space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-slate-800 text-sm" x-text="addr.receiver_name"></span>
                                    <span class="text-slate-300">|</span>
                                    <span class="font-semibold text-slate-600" x-text="addr.receiver_phone"></span>
                                    <template x-if="addr.is_default">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary text-white">
                                            Mặc định
                                        </span>
                                    </template>
                                </div>
                                <p class="font-bold text-slate-800" x-text="addr.detail_address"></p>
                                <p class="text-slate-500" x-text="formatFullAddress(addr)"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Actions inside select modal -->
                <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <button type="button" @click="showSelectModal = false; openAddressModal()"
                            class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-primary/30 hover:bg-primary-50 text-primary font-bold text-xs transition-colors flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        <span>Thêm địa chỉ mới</span>
                    </button>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button type="button" @click="showSelectModal = false"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-50 transition-colors">
                            Đóng
                        </button>
                        <button type="button" @click="confirmAddressSelection()"
                                class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-600 text-white font-bold text-xs shadow-xs transition-colors">
                            Xác nhận chọn
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<script>
    function checkoutPage() {
        return {
            addresses: @json($addresses),
            selectedAddressId: {{ $defaultAddress ? $defaultAddress->id : ($addresses->first() ? $addresses->first()->id : 'null') }},
            tempSelectedAddressId: null,
            allowCod: {{ ($allowCod ?? true) ? 'true' : 'false' }},
            paymentMethod: '{{ old('payment_method', 'contact_shop') }}',
            totalPrice: {{ (float) $cart->total_price }},

            get isCodWithDeposit() {
                return this.paymentMethod === 'cod' && this.totalPrice > 400000;
            },
            get depositAmount() {
                return this.isCodWithDeposit ? Math.round(this.totalPrice * 0.40) : 0;
            },
            get remainingAmount() {
                return this.totalPrice - this.depositAmount;
            },
            formatCurrency(val) {
                return new Intl.NumberFormat('vi-VN').format(val) + ' đ';
            },
            
            showAddressModal: false,
            showSelectModal: false,
            
            isSavingAddress: false,
            isSubmitting: false,
            formError: '',
            
            form: {
                receiver_name: '{{ addslashes($user->name) }}',
                receiver_phone: '{{ addslashes($user->phone ?? "") }}',
                province: '',
                district: '',
                ward: '',
                detail_address: '',
                is_default: true,
            },

            init() {
                if (this.addresses.length > 0 && !this.selectedAddressId) {
                    const def = this.addresses.find(a => a.is_default);
                    this.selectedAddressId = def ? def.id : this.addresses[0].id;
                }
            },

            get selectedAddress() {
                if (!this.selectedAddressId || !this.addresses.length) return null;
                return this.addresses.find(a => a.id == this.selectedAddressId) || null;
            },

            formatFullAddress(addr) {
                if (!addr) return '';
                if (addr.full_address) return addr.full_address;
                const parts = [addr.detail_address, addr.ward, addr.district, addr.province].filter(Boolean);
                return parts.join(', ');
            },

            openAddressModal() {
                this.formError = '';
                // Pre-fill user name and phone
                this.form.receiver_name = this.form.receiver_name || '{{ addslashes($user->name) }}';
                this.form.receiver_phone = this.form.receiver_phone || '{{ addslashes($user->phone ?? "") }}';
                this.form.is_default = this.addresses.length === 0;
                this.showAddressModal = true;
            },

            openSelectModal() {
                this.tempSelectedAddressId = this.selectedAddressId;
                this.showSelectModal = true;
            },

            confirmAddressSelection() {
                if (this.tempSelectedAddressId) {
                    this.selectedAddressId = this.tempSelectedAddressId;
                }
                this.showSelectModal = false;
            },

            async saveAddress() {
                this.formError = '';
                
                // Validate fields
                if (!this.form.receiver_name.trim()) {
                    this.formError = 'Vui lòng nhập họ tên người nhận.';
                    return;
                }
                if (!this.form.receiver_phone.trim()) {
                    this.formError = 'Vui lòng nhập số điện thoại người nhận.';
                    return;
                }
                if (!this.form.province) {
                    this.formError = 'Vui lòng chọn Tỉnh / Thành phố.';
                    return;
                }
                if (!this.form.detail_address.trim()) {
                    this.formError = 'Vui lòng nhập địa chỉ cụ thể (Số nhà, ngõ, tên đường).';
                    return;
                }

                this.isSavingAddress = true;

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        || '{{ csrf_token() }}';

                    const response = await fetch('{{ route("account.addresses.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({
                            receiver_name: this.form.receiver_name,
                            receiver_phone: this.form.receiver_phone,
                            province: this.form.province,
                            district: this.form.district || null,
                            ward: this.form.ward || null,
                            detail_address: this.form.detail_address,
                            is_default: this.form.is_default ? 1 : 0,
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        const saved = data.address;
                        
                        // If marked as default, unset previous default
                        if (saved.is_default) {
                            this.addresses.forEach(a => a.is_default = false);
                        }

                        // Add saved address to list
                        this.addresses.unshift(saved);

                        // Select newly created address
                        this.selectedAddressId = saved.id;

                        // Close modals
                        this.showAddressModal = false;
                        this.showSelectModal = false;

                        // Clear detail address for next time
                        this.form.detail_address = '';
                        this.form.district = '';
                        this.form.ward = '';
                    } else {
                        this.formError = data.message || 'Không thể lưu địa chỉ. Vui lòng kiểm tra lại.';
                    }
                } catch (err) {
                    this.formError = 'Lỗi kết nối tới máy chủ. Vui lòng thử lại!';
                } finally {
                    this.isSavingAddress = false;
                }
            },

            handleSubmit(event) {
                if (this.isSubmitting) {
                    event.preventDefault();
                    return false;
                }
                if (!this.selectedAddressId) {
                    event.preventDefault();
                    this.openAddressModal();
                    return false;
                }
                this.isSubmitting = true;
            }
        };
    }
</script>
@endsection
