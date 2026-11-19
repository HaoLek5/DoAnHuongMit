@extends('layouts.admin')

@section('title', 'Chi tiết đơn hàng #' . $order->order_code . ' - Hương Mít Admin')
@section('page_title', 'Chi tiết Đơn hàng')

@section('content')
<div class="space-y-6">

    <!-- Top Action Bar -->
    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-100 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.orders.index') }}" 
               class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors"
               title="Quay lại danh sách">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h2 class="text-lg font-black text-slate-900 font-mono">#{{ $order->order_code }}</h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $order->status_badge_class }}">
                        {{ $order->status_label }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Thời gian đặt: {{ $order->created_at->format('d/m/Y H:i:s') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            @if($order->order_status === 'pending')
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="inline">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="order_status" value="confirmed">
                    <button type="submit" 
                            class="px-4 py-2.5 rounded-2xl bg-primary hover:bg-primary-600 text-white font-bold text-xs shadow-xs transition-all flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>Xác nhận đơn</span>
                    </button>
                </form>
            @elseif(in_array($order->order_status, ['confirmed', 'processing', 'shipping'], true))
                <div class="hidden" aria-hidden="true">
                    <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="order_status" value="delivered">
                        <button type="submit">Đánh dấu Giao thành công</button>
                    </form>
                </div>
            @endif

            @if($order->order_status !== 'cancelled')
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="inline"
                      onsubmit="return confirm('Xác nhận hoàn đơn hàng #{{ $order->order_code }}? Toàn bộ sản phẩm trong đơn sẽ được tự động hoàn trả lại kho!')">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="order_status" value="cancelled">
                    <input type="hidden" name="admin_note" value="Hàng bị hoàn về - Quản trị viên hoàn đơn nhập kho.">
                    <button type="submit"
                            class="px-4 py-2.5 rounded-2xl bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-200 font-bold text-xs shadow-xs transition-all flex items-center gap-1.5"
                            title="Hoàn đơn và nhập lại kho">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        <span>Hoàn đơn</span>
                    </button>
                </form>
            @endif

            <a href="{{ route('admin.orders.print', $order) }}" target="_blank"
               class="px-5 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-xs transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>In phiếu giao hàng</span>
            </a>
        </div>
    </div>

    @if($order->order_status === 'cancel_pending')
        <div class="p-5 sm:p-6 rounded-3xl bg-rose-50 border border-rose-200 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start gap-3">
                <span class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Khách hàng đã yêu cầu hủy đơn hàng này</h3>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Đơn hàng đang chờ xử lý hủy. Bạn có thể <strong>Duyệt hủy đơn</strong> để hoàn trả hàng vào kho, hoặc <strong>Từ chối hủy</strong> để tiếp tục đơn hàng.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 self-end sm:self-auto">
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="order_status" value="pending">
                    <input type="hidden" name="admin_note" value="Shop đã từ chối yêu cầu hủy đơn.">
                    <button type="submit" 
                            class="px-4 py-2.5 rounded-2xl bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs border border-slate-200 shadow-xs transition-colors">
                        Từ chối hủy
                    </button>
                </form>

                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST"
                      onsubmit="return confirm('Bạn có chắc chắn muốn duyệt hủy đơn hàng này? Toàn bộ số lượng sản phẩm trong đơn sẽ được hoàn trả lại kho!')">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="order_status" value="cancelled">
                    <input type="hidden" name="admin_note" value="Shop đã duyệt yêu cầu hủy đơn của khách hàng.">
                    <button type="submit" 
                            class="px-5 py-2.5 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Duyệt hủy đơn (Hoàn kho)</span>
                    </button>
                </form>
            </div>
        </div>
    @elseif($order->order_status === 'cancelled')
        <div class="p-4 rounded-2xl bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-600 flex items-center gap-2.5">
            <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Đơn hàng này đã bị hủy. Hàng hóa đã được hoàn lại về kho hệ thống.</span>
        </div>
    @elseif($order->order_status === 'delivered')
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2.5">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>Đơn hàng đã giao thành công. Trạng thái thanh toán: <strong>{{ $order->payment_status_label }}</strong>.</span>
        </div>
    @endif

    @if($order->bankTransactions->where('reconciliation_status', 'needs_review')->isNotEmpty())
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <span class="font-bold">Lưu ý đối soát chuyển khoản:</span>
                <span>Đơn hàng này có giao dịch ngân hàng chênh lệch hoặc chuyển trùng cần kiểm tra (xem chi tiết ở mục Đối soát VietQR).</span>
            </div>
            <a href="{{ route('admin.bank-transactions.index', ['search' => $order->order_code]) }}"
               class="px-3.5 py-1.5 rounded-xl bg-amber-600 text-white font-bold whitespace-nowrap self-start sm:self-auto">
                Xem đối soát
            </a>
        </div>
    @endif

    <!-- Main Content: 2 Cards (Sản phẩm trong đơn hàng & Thông tin giao hàng) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Card 1: Sản phẩm trong đơn hàng (8 cols) -->
        <div class="lg:col-span-8 bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </span>
                    <h3 class="text-base font-bold text-slate-800">Sản phẩm trong đơn hàng</h3>
                </div>
                <span class="text-xs font-semibold text-slate-400">{{ $order->items->count() }} sản phẩm</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50/80 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3.5 px-6">Sản phẩm</th>
                            <th class="py-3.5 px-4 text-center">Đơn giá</th>
                            <th class="py-3.5 px-4 text-center">Số lượng</th>
                            <th class="py-3.5 px-6 text-right">Thành tiền</th>
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
                                            <div class="flex items-center gap-2 mt-0.5">
                                                @if($item->variant_name && $item->variant_name !== 'Mặc định')
                                                    <span class="text-[10px] font-semibold text-primary bg-primary-50 px-2 py-0.5 rounded-md">
                                                        {{ $item->variant_name }}
                                                    </span>
                                                @endif
                                                <span class="text-[10px] text-slate-400 font-mono">{{ $item->sku }}</span>
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
                                <td class="py-4 px-6 text-right font-extrabold text-slate-900">
                                    {{ number_format($item->subtotal, 0, ',', '.') }} đ
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Financial Calculation Summary -->
            <div class="p-6 bg-slate-50/60 border-t border-slate-100 flex flex-col items-end space-y-2 text-xs">
                <div class="w-full max-w-sm space-y-2.5">
                    <div class="flex justify-between text-slate-500">
                        <span>Tạm tính hàng hóa:</span>
                        <span class="font-bold text-slate-800">{{ number_format($order->subtotal, 0, ',', '.') }} đ</span>
                    </div>
                    @if($order->discount_amount > 0)
                        <div class="flex justify-between text-slate-500">
                            <span>Giảm giá / Voucher:</span>
                            <span class="font-semibold text-slate-700">-{{ number_format($order->discount_amount, 0, ',', '.') }} đ</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-slate-500">
                        <span>Phí vận chuyển:</span>
                        <span class="font-semibold text-slate-700">Thanh toán khi nhận hàng</span>
                    </div>
                    @if($order->deposit_required && (float) $order->deposit_amount > 0 && (float) $order->paid_amount > 0)
                        <div class="flex justify-between text-emerald-700 font-semibold">
                            <span>Đã cọc VietQR (40%):</span>
                            <span class="font-bold">-{{ number_format($order->deposit_amount, 0, ',', '.') }} đ</span>
                        </div>
                        @if($order->payment_status === 'paid' && (float) $order->remaining_amount <= 0)
                            <div class="flex justify-between text-emerald-700 font-semibold">
                                <span>Đã thu khi giao (COD còn lại):</span>
                                <span class="font-bold">-{{ number_format(max(0, (float) $order->total_amount - (float) $order->deposit_amount), 0, ',', '.') }} đ</span>
                            </div>
                            <div class="pt-2 border-t border-dashed border-slate-200 flex justify-between items-baseline">
                                <span class="text-sm font-bold text-emerald-900">Còn phải thu:</span>
                                <span class="text-xl font-black text-emerald-700">0 đ</span>
                            </div>
                        @else
                            <div class="pt-2 border-t border-dashed border-slate-200 flex justify-between items-baseline">
                                <span class="text-sm font-bold text-amber-900">Shipper thu khi giao (60%):</span>
                                <span class="text-xl font-black text-amber-700">{{ number_format($order->remaining_amount, 0, ',', '.') }} đ</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-[11px] text-slate-500">
                            <span>Tổng giá trị đơn hàng:</span>
                            <span class="font-bold">{{ number_format($order->total_amount, 0, ',', '.') }} đ</span>
                        </div>
                    @else
                        <div class="pt-2 border-t border-dashed border-slate-200 flex justify-between items-baseline">
                            <span class="text-sm font-bold text-slate-800">Tổng thanh toán:</span>
                            <span class="text-xl font-black text-primary">{{ number_format($order->total_amount, 0, ',', '.') }} đ</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card 2: Thông tin giao hàng (4 cols) -->
        <div class="lg:col-span-4 bg-white rounded-3xl border border-slate-100 p-6 shadow-sm space-y-5">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                    </svg>
                </span>
                <h3 class="text-base font-bold text-slate-800">Thông tin giao hàng</h3>
            </div>

            <div class="space-y-4 text-xs">
                <!-- Receiver info -->
                <div>
                    <span class="text-slate-400 block mb-1">Người nhận hàng</span>
                    <div class="font-bold text-slate-800 text-sm">{{ $order->receiver_name }}</div>
                    <div class="font-bold text-slate-700 mt-0.5 font-mono">{{ $order->receiver_phone }}</div>
                </div>

                <!-- Shipping address -->
                <div class="pt-3 border-t border-slate-100">
                    <span class="text-slate-400 block mb-1">Địa chỉ nhận hàng</span>
                    <div class="font-medium text-slate-700 leading-relaxed bg-slate-50 p-3 rounded-2xl border border-slate-100">
                        {{ $order->shipping_address }}
                    </div>
                </div>

                <!-- Shipping Carrier info -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-slate-400">Đơn vị vận chuyển:</span>
                    <span class="font-bold text-slate-800">{{ $order->shipping_carrier }}</span>
                </div>

                <!-- Payment info -->
                <div class="pt-3 border-t border-slate-100 space-y-2">
                    <span class="text-slate-400 block mb-1">Phương thức & Trạng thái thanh toán</span>
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-bold text-slate-800">{{ $order->payment_method_label }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : ($order->payment_status === 'deposit_paid' ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-700') }}">
                            {{ $order->payment_status_label }}
                        </span>
                    </div>
                    @if($order->deposit_required && (float) $order->deposit_amount > 0 && (float) $order->paid_amount > 0)
                        <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 font-semibold space-y-1">
                            <div class="flex justify-between">
                                <span>Tiền đã cọc (VietQR 40%):</span>
                                <span class="font-extrabold text-amber-700">{{ number_format($order->deposit_amount, 0, ',', '.') }} đ</span>
                            </div>
                            <div class="flex justify-between text-[11px] text-slate-700 pt-1 border-t border-amber-200">
                                <span>{{ $order->payment_status === 'paid' ? 'Đã thu phần COD còn lại:' : 'Shipper cần thu (60% COD):' }}</span>
                                <span class="font-extrabold {{ $order->payment_status === 'paid' ? 'text-emerald-700' : 'text-amber-900' }}">
                                    {{ $order->payment_status === 'paid' ? number_format(max(0, (float) $order->total_amount - (float) $order->deposit_amount), 0, ',', '.') . ' đ (Hoàn tất)' : number_format($order->remaining_amount, 0, ',', '.') . ' đ' }}
                                </span>
                            </div>
                        </div>
                    @elseif($order->payment_method === 'bank_transfer')
                        <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 font-semibold space-y-1">
                            <div class="flex justify-between">
                                <span>Tiền đã thanh toán:</span>
                                <span class="font-extrabold">{{ number_format($order->paid_amount, 0, ',', '.') }} đ</span>
                            </div>
                            <div class="flex justify-between text-[11px] text-emerald-700">
                                <span>Cần thu khi giao:</span>
                                <span class="font-extrabold text-emerald-900">0 đ (Đã thanh toán trước)</span>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center justify-between text-slate-600">
                            <span>{{ $order->payment_status === 'paid' ? 'Đã thu COD:' : 'Tiền thu khi nhận (COD):' }}</span>
                            <span class="font-extrabold text-sm {{ $order->payment_status === 'paid' ? 'text-emerald-700' : 'text-primary' }}">
                                {{ number_format($order->payment_status === 'paid' ? $order->paid_amount : $order->total_amount, 0, ',', '.') }} đ
                            </span>
                        </div>
                    @endif
                </div>

                @if($order->payments->isNotEmpty())
                    <div class="pt-3 border-t border-slate-100 space-y-2">
                        <span class="text-slate-400 block">Lịch sử giao dịch thanh toán</span>
                        <div class="space-y-2">
                            @foreach($order->payments as $payment)
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-[11px] space-y-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-bold text-slate-800">
                                            {{ $payment->payment_type === 'deposit' ? 'Đặt cọc 40% (VietQR)' : ($payment->payment_method === 'cod' ? 'Thu COD' : 'Thanh toán chuyển khoản') }}
                                        </span>
                                        <span class="font-mono font-bold {{ $payment->status === 'paid' ? 'text-emerald-700' : 'text-slate-500' }}">
                                            {{ number_format($payment->amount, 0, ',', '.') }} đ
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-[10px] text-slate-500">
                                        <span>{{ $payment->status === 'paid' ? 'Đã thu' : ($payment->status === 'refunded' ? 'Đã hoàn tiền' : 'Chờ thu') }}</span>
                                        @if($payment->transaction_code)
                                            <span class="font-mono text-slate-600">Ref: {{ $payment->transaction_code }}</span>
                                        @endif
                                    </div>
                                    @if($payment->paid_at)
                                        <div class="text-[10px] text-slate-400">Lúc: {{ $payment->paid_at->format('d/m/Y H:i') }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Customer account info -->
                <div class="pt-3 border-t border-slate-100">
                    <span class="text-slate-400 block mb-1">Tài khoản đặt hàng</span>
                    <div class="font-bold text-slate-800">{{ $order->user?->name ?? 'Khách vãng lai' }}</div>
                    @if($order->user?->email)
                        <div class="text-slate-500 text-[11px]">{{ $order->user->email }}</div>
                    @endif
                </div>

                <!-- Order note -->
                @if($order->note)
                    <div class="pt-3 border-t border-slate-100">
                        <span class="text-slate-400 block mb-1">Ghi chú của khách hàng</span>
                        <div class="p-3 rounded-2xl bg-amber-50/60 border border-amber-100 text-slate-700 whitespace-pre-line leading-relaxed font-medium">
                            {{ $order->note }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
