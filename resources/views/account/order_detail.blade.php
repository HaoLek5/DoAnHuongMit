@extends('account.layout')

@section('title', 'Chi tiết đơn hàng #' . $order->order_code . ' - Hương Mít')
@section('page_name', 'Chi tiết đơn hàng')

@section('account_content')
<div class="space-y-6" x-data="{ 
    showCancelModal: false, 
    showAddressModal: false, 
    addressTab: '{{ $savedAddresses->isNotEmpty() ? 'saved' : 'new' }}',
    selectedAddressId: '{{ $order->address_id ?? ($savedAddresses->first()?->id ?? '') }}'
}">

    <!-- Header & Back Button -->
    <div class="bg-white rounded-3xl border border-primary-100/80 p-6 sm:p-7 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('account.orders.index') }}" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-primary hover:text-white text-slate-600 flex items-center justify-center transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-800">
                        Chi tiết đơn hàng <span class="font-mono text-primary">#{{ $order->order_code }}</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Đặt lúc: {{ $order->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @php
                $isConfirmedState = in_array($order->order_status, ['confirmed', 'processing', 'shipping', 'delivered'], true);
                $isCancelledState = in_array($order->order_status, ['cancelled', 'cancel_pending'], true);
                $customerBadgeClass = $isConfirmedState ? 'bg-blue-50 text-blue-700 border-blue-200' : $order->status_badge_class;
                $customerStatusLabel = $isConfirmedState ? 'Đã xác nhận' : ($isCancelledState ? $order->status_label : 'Đã đặt hàng');
            @endphp
            <span class="px-3 py-1.5 rounded-full text-xs font-bold border {{ $customerBadgeClass }}">
                {{ $customerStatusLabel }}
            </span>
            @if($order->order_status === 'delivered')
                <span class="sr-only">Giao thành công</span>
            @endif

            @if($order->order_status === 'pending')
                <button type="button" @click="showCancelModal = true"
                        class="px-3.5 py-1.5 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-xs transition-colors">
                    Hủy đơn hàng
                </button>
            @endif
        </div>
    </div>

    <!-- Status Tracking Timeline or Alerts -->
    @if($order->order_status === 'cancel_pending')
        <div class="p-5 rounded-3xl bg-amber-50 border border-amber-200 text-xs text-amber-800 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <span class="font-bold text-sm block text-amber-900">Đơn hàng đang chờ shop xác nhận hủy</span>
                <span class="text-amber-700 block mt-1">Yêu cầu hủy đơn đã được gửi tới hệ thống. Vui lòng chờ nhân viên tiếp nhận và xử lý.</span>
            </div>
        </div>
    @elseif($order->order_status === 'cancelled')
        <div class="p-5 rounded-3xl bg-rose-50 border border-rose-200 text-xs text-rose-700 flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <div>
                <span class="font-bold">Đơn hàng này đã bị hủy.</span>
                <span class="text-rose-600 block mt-0.5">Số lượng hàng đã được hoàn trả lại về kho hệ thống.</span>
            </div>
        </div>
    @else
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-7 shadow-xs">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-6">Trạng thái đơn hàng</h3>
            
            @php
                $steps = [
                    ['status' => 'pending', 'label' => 'Đã đặt hàng', 'desc' => 'Đơn hàng đã được tạo'],
                    ['status' => 'confirmed', 'label' => 'Đã xác nhận', 'desc' => 'Nhân viên đã xác nhận'],
                ];
                $currentStep = ($order->order_status === 'pending') ? 1 : 2;
            @endphp

            <div class="grid grid-cols-2 max-w-sm mx-auto gap-6 sm:gap-12 relative">
                @foreach($steps as $idx => $step)
                    @php $stepNum = $idx + 1; @endphp
                    <div class="flex flex-col items-center text-center">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-bold text-sm mb-2 transition-all
                            {{ $stepNum <= $currentStep ? 'bg-primary text-white shadow-md shadow-primary/25' : 'bg-slate-100 text-slate-400' }}">
                            @if($stepNum < $currentStep || ($currentStep === 2 && $stepNum === 2))
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $stepNum }}
                            @endif
                        </div>
                        <span class="text-xs font-bold {{ $stepNum <= $currentStep ? 'text-slate-800' : 'text-slate-400' }}">{{ $step['label'] }}</span>
                        <span class="text-[10px] text-slate-400 mt-0.5">{{ $step['desc'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Information Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Recipient Information -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-primary-50 text-primary flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                    </span>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Địa chỉ giao hàng</h3>
                </div>

                @if($order->order_status === 'pending')
                    <button type="button" @click="showAddressModal = true"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-primary-50 text-primary hover:bg-primary hover:text-white font-bold text-xs transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        <span>Đổi địa chỉ</span>
                    </button>
                @endif
            </div>

            <div class="space-y-1.5 text-xs">
                <div class="font-bold text-slate-800 text-sm">{{ $order->receiver_name }}</div>
                <div class="text-slate-600 font-semibold flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span>{{ $order->receiver_phone }}</span>
                </div>
                <div class="text-slate-600 leading-relaxed pt-1">{{ $order->shipping_address }}</div>
                @if($order->note)
                    <div class="pt-2 text-[11px] text-slate-500 italic bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                        <strong>Ghi chú:</strong> "{{ $order->note }}"
                    </div>
                @endif
            </div>
        </div>

        <!-- Payment & Shipping Method -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs space-y-3">
            <div class="flex items-center gap-2.5 pb-2 border-b border-slate-100">
                <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Thanh toán & Vận chuyển</h3>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block mb-0.5">Hình thức thanh toán:</span>
                    <span class="font-bold text-slate-800">
                        @if($order->deposit_required && $order->paid_amount > 0)
                            Thanh toán khi nhận hàng (Đã cọc 40% VietQR)
                        @else
                            {{ $order->payment_method_label }}
                        @endif
                    </span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5">Trạng thái thanh toán:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : ($order->payment_status === 'deposit_paid' ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-700') }}">
                        {{ $order->payment_status_label }}
                    </span>
                    @if($order->deposit_required && (float) $order->deposit_amount > 0 && (float) $order->paid_amount > 0)
                        <div class="mt-1 text-[11px] text-amber-800 font-semibold">
                            (Đã cọc {{ number_format($order->deposit_amount, 0, ',', '.') }} đ qua VietQR)
                        </div>
                    @endif
                </div>
                <div>
                    <span class="text-slate-400 block mb-0.5">Đơn vị vận chuyển:</span>
                    <span class="font-bold text-slate-800">{{ $order->shipping_carrier }}</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Ordered Items Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-xs">
        <div class="p-6 pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-800">Danh sách sản phẩm trong đơn</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3.5 px-6">Sản phẩm</th>
                        <th class="py-3.5 px-4 text-center">Đơn giá</th>
                        <th class="py-3.5 px-4 text-center">Số lượng</th>
                        <th class="py-3.5 px-6 text-right">Tạm tính</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($order->items as $item)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden p-1 flex-shrink-0 flex items-center justify-center">
                                        @if($item->variant && $item->variant->product && $item->variant->product->primaryImage)
                                            <img src="{{ $item->variant->product->primaryImage->image_url }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover rounded-lg">
                                        @else
                                            <span class="text-[10px] text-slate-400">Ảnh</span>
                                        @endif
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-slate-800">{{ $item->product_name }}</h4>
                                        <div class="mt-0.5">
                                            @if($item->variant_name && $item->variant_name !== 'Mặc định')
                                                <span class="text-[10px] font-semibold text-primary bg-primary-50 px-2 py-0.5 rounded-md inline-block">
                                                    {{ $item->variant_name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center font-semibold text-slate-700">
                                {{ number_format($item->price, 0, ',', '.') }} đ
                            </td>
                            <td class="py-4 px-4 text-center font-bold text-slate-800">
                                {{ $item->quantity }}
                            </td>
                            <td class="py-4 px-6 text-right font-bold text-slate-800">
                                {{ number_format($item->subtotal, 0, ',', '.') }} đ
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Order Total Summary -->
        <div class="p-6 bg-slate-50/70 border-t border-slate-100 flex flex-col items-end space-y-2 text-xs">
            <div class="w-full max-w-sm space-y-2">
                <div class="flex justify-between text-slate-500">
                    <span>Tạm tính hàng hóa:</span>
                    <span class="font-bold text-slate-800">{{ number_format($order->subtotal, 0, ',', '.') }} đ</span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>Phí vận chuyển:</span>
                    <span class="font-semibold text-slate-700">Thanh toán khi nhận hàng</span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>Tổng đơn hàng:</span>
                    <span class="font-bold text-slate-800">{{ number_format($order->total_amount, 0, ',', '.') }} đ</span>
                </div>
                @if($order->deposit_required && (float) $order->deposit_amount > 0 && (float) $order->paid_amount > 0)
                    <div class="flex justify-between text-emerald-700 font-semibold">
                        <span>Đã đặt cọc (VietQR 40%):</span>
                        <span class="font-bold">-{{ number_format($order->deposit_amount, 0, ',', '.') }} đ</span>
                    </div>
                    @if($order->payment_status === 'paid' && (float) $order->remaining_amount <= 0)
                        <div class="flex justify-between text-emerald-700 font-semibold">
                            <span>Đã thanh toán khi nhận hàng (COD 60%):</span>
                            <span class="font-bold">-{{ number_format(max(0, (float) $order->total_amount - (float) $order->deposit_amount), 0, ',', '.') }} đ</span>
                        </div>
                        <div class="pt-2 border-t border-dashed border-slate-200 flex justify-between items-baseline">
                            <span class="text-sm font-bold text-emerald-900">Số tiền còn lại cần thu:</span>
                            <span class="text-xl font-extrabold text-emerald-700">0 đ</span>
                        </div>
                    @else
                        <div class="pt-2 border-t border-dashed border-slate-200 flex justify-between items-baseline">
                            <span class="text-sm font-bold text-amber-900">Cần thanh toán khi nhận hàng (COD):</span>
                            <span class="text-xl font-extrabold text-amber-700">{{ number_format($order->remaining_amount, 0, ',', '.') }} đ</span>
                        </div>
                    @endif
                @else
                    <div class="pt-2 border-t border-dashed border-slate-200 flex justify-between items-baseline">
                        <span class="text-sm font-bold text-slate-800">Tổng thanh toán:</span>
                        <span class="text-xl font-extrabold text-primary">{{ number_format($order->total_amount, 0, ',', '.') }} đ</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Change Shipping Address Modal -->
    @if($order->order_status === 'pending')
        <div x-show="showAddressModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="showAddressModal = false"></div>
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl space-y-5" @click.stop>
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <span class="w-9 h-9 rounded-xl bg-primary-50 text-primary flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Thay đổi địa chỉ giao hàng</h3>
                                <p class="text-xs text-slate-400">Chỉ áp dụng khi đơn hàng đang chờ xác nhận</p>
                            </div>
                        </div>
                        <button type="button" @click="showAddressModal = false" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Navigation Tabs if user has saved addresses -->
                    @if($savedAddresses->isNotEmpty())
                        <div class="flex items-center bg-slate-100 p-1 rounded-2xl text-xs font-semibold">
                            <button type="button" @click="addressTab = 'saved'" 
                                    class="flex-1 py-2 rounded-xl transition-all"
                                    :class="addressTab === 'saved' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                                Sổ địa chỉ ({{ $savedAddresses->count() }})
                            </button>
                            <button type="button" @click="addressTab = 'new'" 
                                    class="flex-1 py-2 rounded-xl transition-all"
                                    :class="addressTab === 'new' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                                Nhập địa chỉ mới
                            </button>
                        </div>
                    @endif

                    <form action="{{ route('account.orders.update-address', $order->order_code) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <!-- Tab 1: Saved Addresses List -->
                        @if($savedAddresses->isNotEmpty())
                            <div x-show="addressTab === 'saved'" class="space-y-3 max-h-72 overflow-y-auto pr-1">
                                @foreach($savedAddresses as $addr)
                                    <label class="block p-3.5 rounded-2xl border cursor-pointer transition-all hover:border-primary/50"
                                           :class="selectedAddressId == '{{ $addr->id }}' ? 'border-primary bg-primary-50/20 ring-1 ring-primary' : 'border-slate-200'">
                                        <div class="flex items-start gap-3">
                                            <input type="radio" name="address_id" value="{{ $addr->id }}" 
                                                   x-model="selectedAddressId"
                                                   class="mt-1 text-primary focus:ring-primary/20">
                                            <div class="flex-1 min-w-0 text-xs">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-800">{{ $addr->receiver_name }}</span>
                                                    <span class="text-slate-400">|</span>
                                                    <span class="font-semibold text-slate-600 font-mono">{{ $addr->receiver_phone }}</span>
                                                    @if($addr->is_default)
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary-100 text-primary">Mặc định</span>
                                                    @endif
                                                </div>
                                                <p class="text-slate-600 mt-1 leading-relaxed">{{ $addr->full_address }}</p>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <!-- Tab 2: Enter New Address -->
                        <div x-show="addressTab === 'new'" class="space-y-3 text-xs">
                            <input type="hidden" name="address_id" value="new" :disabled="addressTab === 'saved'">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Họ tên người nhận <span class="text-rose-500">*</span></label>
                                    <input type="text" name="receiver_name" :required="addressTab === 'new'" placeholder="Nguyễn Văn A"
                                           class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Số điện thoại <span class="text-rose-500">*</span></label>
                                    <input type="tel" name="receiver_phone" :required="addressTab === 'new'" placeholder="0987654321"
                                           class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Tỉnh / Thành phố <span class="text-rose-500">*</span></label>
                                <select name="province" :required="addressTab === 'new'"
                                        class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none bg-white">
                                    <option value="">-- Chọn Tỉnh / Thành phố --</option>
                                    <option value="Hà Nội">Hà Nội</option>
                                    <option value="TP. Hồ Chí Minh">TP. Hồ Chí Minh</option>
                                    <option value="Đà Nẵng">Đà Nẵng</option>
                                    <option value="Hải Phòng">Hải Phòng</option>
                                    <option value="Cần Thơ">Cần Thơ</option>
                                    <option value="Bình Dương">Bình Dương</option>
                                    <option value="Đồng Nai">Đồng Nai</option>
                                    <option value="Quảng Ninh">Quảng Ninh</option>
                                    <option value="Bắc Ninh">Bắc Ninh</option>
                                    <option value="Hải Dương">Hải Dương</option>
                                    <option value="Hưng Yên">Hưng Yên</option>
                                    <option value="Vĩnh Phúc">Vĩnh Phúc</option>
                                    <option value="Khác">Tỉnh thành khác...</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Quận / Huyện</label>
                                    <input type="text" name="district" placeholder="Quận / Huyện"
                                           class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Phường / Xã</label>
                                    <input type="text" name="ward" placeholder="Phường / Xã"
                                           class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Địa chỉ chi tiết (số nhà, ngõ, đường...) <span class="text-rose-500">*</span></label>
                                <input type="text" name="detail_address" :required="addressTab === 'new'" placeholder="Số 123 đường ABC..."
                                       class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                            </div>

                            <div class="pt-1 flex items-center gap-2">
                                <input type="checkbox" id="save_to_book" name="save_to_book" value="1" checked
                                       class="rounded border-slate-300 text-primary focus:ring-primary/20">
                                <label for="save_to_book" class="text-xs font-semibold text-slate-700 cursor-pointer select-none">
                                    Lưu vào sổ địa chỉ để sử dụng cho lần sau
                                </label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                            <button type="button" @click="showAddressModal = false"
                                    class="flex-1 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                                Đóng lại
                            </button>
                            <button type="submit" 
                                    class="flex-1 py-2.5 rounded-xl bg-primary hover:bg-primary-600 text-white font-bold text-xs shadow-xs transition-colors">
                                Cập nhật địa chỉ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Cancel Order Confirmation Modal -->
    @if($order->order_status === 'pending')
        <div x-show="showCancelModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="showCancelModal = false"></div>
            <div class="min-h-full flex items-center justify-center p-4">
                <div class="relative bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl space-y-4" @click.stop>
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="text-center">
                        <h3 class="text-base font-bold text-slate-900">Xác nhận yêu cầu hủy đơn hàng</h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Bạn có chắc chắn muốn hủy đơn hàng <strong class="text-slate-800">#{{ $order->order_code }}</strong>? Đơn hàng sẽ được chuyển sang trạng thái <strong>Chờ hủy</strong> để shop xử lý.
                        </p>
                    </div>

                    <form action="{{ route('account.orders.cancel', $order->order_code) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Lý do hủy đơn (tùy chọn):</label>
                            <select name="cancel_reason" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none bg-white">
                                <option value="Muốn thay đổi địa chỉ nhận hàng">Muốn thay đổi địa chỉ nhận hàng</option>
                                <option value="Muốn thay đổi sản phẩm trong đơn">Muốn thay đổi sản phẩm trong đơn</option>
                                <option value="Tìm thấy giá rẻ hơn ở nơi khác">Tìm thấy giá rẻ hơn ở nơi khác</option>
                                <option value="Đặt nhầm sản phẩm / không có nhu cầu nữa">Đặt nhầm sản phẩm / không có nhu cầu nữa</option>
                                <option value="Thời gian giao hàng lâu">Thời gian giao hàng lâu</option>
                                <option value="Lý do khác">Lý do khác</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button type="button" @click="showCancelModal = false"
                                    class="flex-1 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition-colors">
                                Giữ lại đơn
                            </button>
                            <button type="submit" 
                                    class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-colors">
                                Gửi yêu cầu hủy
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection
