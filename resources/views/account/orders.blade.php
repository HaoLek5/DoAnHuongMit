@extends('account.layout')

@section('title', 'Đơn hàng của tôi - Hương Mít')
@section('page_name', 'Đơn hàng của tôi')

@section('account_content')
<div class="space-y-6">

    <!-- Header & Status Filter Tabs -->
    <div class="bg-white rounded-3xl border border-primary-100/80 p-6 sm:p-7 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
            <div>
                <h2 class="text-base sm:text-lg font-bold text-slate-800">Lịch sử đơn hàng</h2>
                <p class="text-xs text-slate-400 mt-0.5">Theo dõi quá trình vận chuyển và trạng thái xử lý các đơn hàng bạn đã đặt</p>
            </div>

            <a href="{{ route('home') }}" class="px-4 py-2.5 rounded-2xl bg-primary-50 hover:bg-primary-100 text-primary font-bold text-xs transition-colors self-start sm:self-auto flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span>Mua sắm thêm</span>
            </a>
        </div>

        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none text-xs font-semibold">
            <a href="{{ route('account.orders.index') }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ !$status ? 'bg-primary text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Tất cả ({{ $counts['all'] }})
            </a>
            <a href="{{ route('account.orders.index', ['status' => 'pending']) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $status === 'pending' ? 'bg-primary text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Đã đặt hàng ({{ $counts['pending'] }})
            </a>
            <a href="{{ route('account.orders.index', ['status' => 'confirmed']) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $status === 'confirmed' ? 'bg-primary text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Đã xác nhận ({{ $counts['confirmed'] }})
            </a>
            <a href="{{ route('account.orders.index', ['status' => 'delivered']) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $status === 'delivered' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                Giao thành công ({{ $counts['delivered'] }})
            </a>
            <a href="{{ route('account.orders.index', ['status' => 'cancel_pending']) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $status === 'cancel_pending' ? 'bg-rose-600 text-white shadow-xs font-bold' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                Chờ hủy ({{ $counts['cancel_pending'] }})
            </a>
            <a href="{{ route('account.orders.index', ['status' => 'cancelled']) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $status === 'cancelled' ? 'bg-slate-700 text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Đã hủy ({{ $counts['cancelled'] }})
            </a>
        </div>
    </div>

    <!-- Orders List -->
    @if($orders->count() > 0)
        <div class="space-y-4">
            @foreach($orders as $order)
                <div class="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-6 shadow-xs hover:border-primary/50 transition-all space-y-4">
                    
                    <!-- Header of card -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-slate-100 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="font-extrabold text-slate-800 font-mono text-sm">#{{ $order->order_code }}</span>
                            <span class="text-slate-400">|</span>
                            <span class="text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $order->status_badge_class }}">
                                {{ $order->status_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Items Preview -->
                    <div class="space-y-3">
                        @foreach($order->items as $item)
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 p-1 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                    @if($item->variant && $item->variant->product && $item->variant->product->primaryImage)
                                        <img src="{{ $item->variant->product->primaryImage->image_url }}" alt="{{ $item->product_name }}" class="w-full h-full object-cover rounded-lg">
                                    @else
                                        <span class="text-[10px] text-slate-400">Ảnh</span>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0 text-xs">
                                    <h4 class="font-bold text-slate-800 truncate">{{ $item->product_name }}</h4>
                                    <div class="flex items-center gap-2 text-slate-500 mt-0.5">
                                        @if($item->variant_name && $item->variant_name !== 'Mặc định')
                                            <span class="text-primary bg-primary-50 px-1.5 py-0.5 rounded text-[10px] font-medium">
                                                {{ $item->variant_name }}
                                            </span>
                                        @endif
                                        <span>x{{ $item->quantity }}</span>
                                    </div>
                                </div>
                                <div class="text-right text-xs">
                                    <span class="font-bold text-slate-800">{{ number_format($item->subtotal, 0, ',', '.') }} đ</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Footer of card: Total & Buttons -->
                    <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 text-slate-500">
                            <span>Phương thức:</span>
                            <span class="font-semibold text-slate-700">{{ $order->payment_method_label }}</span>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-4">
                            <div>
                                <span class="text-slate-400 mr-1.5">Tổng tiền:</span>
                                <span class="text-base font-extrabold text-primary">{{ number_format($order->total_amount, 0, ',', '.') }} đ</span>
                            </div>

                            <a href="{{ route('account.orders.show', $order->order_code) }}"
                               class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-600 text-white font-bold text-xs shadow-xs transition-colors flex items-center gap-1.5">
                                <span>Chi tiết</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>

                </div>
            @endforeach

            <!-- Pagination -->
            <div class="pt-4">
                {{ $orders->links() }}
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center shadow-xs">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-primary-50 text-primary flex items-center justify-center mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">Chưa có đơn hàng nào</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Bạn chưa có đơn hàng nào trong mục này. Hãy khám phá và mua sắm ngay các sản phẩm mẹ và bé tại Hương Mít nhé!</p>
            <div class="mt-6">
                <a href="{{ route('home') }}" class="px-6 py-3 rounded-2xl bg-gradient-to-r from-primary to-rose-500 text-white font-bold text-xs shadow-md shadow-primary/20 hover:shadow-lg transition-all inline-flex items-center gap-2">
                    <span>Khám phá sản phẩm ngay</span>
                </a>
            </div>
        </div>
    @endif

</div>
@endsection
