@extends('layouts.admin')

@section('title', 'Quản lý Đơn hàng - Hương Mít')
@section('page_title', 'Danh sách Đơn hàng')

@section('content')
<div x-data="adminOrderManager()" class="space-y-5">

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2.5">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800 flex items-center gap-2.5">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Main Order Card with Tabs & Filter Form -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden space-y-4">

        <!-- Status Filter Tabs: Tất cả, Chờ xác nhận, Đã xác nhận, Chờ hủy, Đã hủy -->
        <div class="px-6 pt-5 border-b border-slate-100 flex items-center gap-2 overflow-x-auto pb-4 scrollbar-none text-xs font-semibold">
            @php $currentStatus = request('status'); @endphp
            <a href="{{ route('admin.orders.index', request()->except(['status', 'page'])) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ empty($currentStatus) ? 'bg-primary text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Tất cả ({{ $counts['all'] }})
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $currentStatus === 'pending' ? 'bg-amber-600 text-white shadow-xs font-bold' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                Chờ xác nhận ({{ $counts['pending'] }})
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => 'confirmed'])) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $currentStatus === 'confirmed' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Đã xác nhận ({{ $counts['confirmed'] }})
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => 'delivered'])) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $currentStatus === 'delivered' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                Giao thành công ({{ $counts['delivered'] }})
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => 'cancel_pending'])) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $currentStatus === 'cancel_pending' ? 'bg-rose-600 text-white shadow-xs font-bold' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                Chờ hủy ({{ $counts['cancel_pending'] }})
            </a>
            <a href="{{ route('admin.orders.index', array_merge(request()->except('page'), ['status' => 'cancelled'])) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $currentStatus === 'cancelled' ? 'bg-slate-700 text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Đã hủy ({{ $counts['cancelled'] }})
            </a>
        </div>

        <!-- Filter Search Bar -->
        <div class="px-6 pb-2">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-wrap items-center gap-3">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <!-- Search Input -->
                <div class="relative flex-1 min-w-[240px]">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm theo mã đơn (DH-...), tên khách, SĐT..." 
                           class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                </div>

                <!-- Date Range -->
                <div class="flex items-center gap-2">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-700 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none" title="Từ ngày">
                    <span class="text-xs text-slate-400">-</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-700 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none" title="Đến ngày">
                </div>

                <!-- Submit Button -->
                <button type="submit" class="px-5 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-xs transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Lọc đơn</span>
                </button>

                @if(request()->hasAny(['search', 'date_from', 'date_to']))
                    <a href="{{ route('admin.orders.index', ['status' => request('status')]) }}" 
                       class="px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition-all">
                        Xóa lọc
                    </a>
                @endif
            </form>
        </div>

        <!-- Bulk Action Toolbar (Appears when 1+ orders selected) -->
        <div x-show="selectedOrderIds.length > 0" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="mx-6 p-3 bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-2xl shadow-md flex flex-wrap items-center justify-between gap-3 border border-slate-700/50">
            
            <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-lg bg-primary/20 text-primary-300 flex items-center justify-center font-bold text-xs">
                    <svg class="w-4 h-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </span>
                <span class="text-xs font-semibold text-slate-200">
                    Đã chọn <strong class="text-white font-extrabold text-sm" x-text="selectedOrderIds.length"></strong> đơn hàng
                </span>
                <button type="button" @click="clearSelection()" class="text-[11px] text-slate-400 hover:text-white underline ml-1 cursor-pointer">
                    Bỏ chọn
                </button>
            </div>

            <div class="flex items-center gap-2">
                <!-- Action: In đơn -->
                <button type="button" @click="printSelected()" 
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-100 text-xs font-bold transition-all flex items-center gap-2 border border-slate-600 shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>In đơn</span>
                </button>

                <!-- Action: Xác nhận & In đơn -->
                <button type="button" @click="confirmAndPrintSelected()" 
                        class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-600 text-white text-xs font-bold shadow-md shadow-primary/30 transition-all flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Xác nhận & in đơn</span>
                </button>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/80 border-y border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3.5 pl-6 pr-2 w-10 text-center">
                            <input type="checkbox" 
                                   @change="toggleSelectAll($event)" 
                                   :checked="isAllSelected"
                                   title="Chọn tất cả đơn trên trang này"
                                   class="w-4 h-4 rounded text-primary focus:ring-primary/20 border-slate-300 cursor-pointer">
                        </th>
                        <th class="py-3.5 px-4">Mã đơn & Ngày đặt</th>
                        <th class="py-3.5 px-4">Người nhận & SĐT</th>
                        <th class="py-3.5 px-4">Sản phẩm</th>
                        <th class="py-3.5 px-4 text-right">Tổng tiền</th>
                        <th class="py-3.5 px-4 text-center">Trạng thái</th>
                        <th class="py-3.5 px-6 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="transition-colors"
                            :class="selectedOrderIds.includes({{ $order->id }}) ? 'bg-primary-50/30' : 'hover:bg-slate-50/60'">
                            
                            <!-- Checkbox -->
                            <td class="py-4 pl-6 pr-2 text-center">
                                <input type="checkbox" 
                                       :value="{{ $order->id }}" 
                                       x-model.number="selectedOrderIds"
                                       class="w-4 h-4 rounded text-primary focus:ring-primary/20 border-slate-300 cursor-pointer">
                            </td>

                            <!-- Order code & date -->
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-sm text-primary hover:underline font-mono">
                                        #{{ $order->order_code }}
                                    </a>
                                    @if($order->payment_method === 'bank_transfer')
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200" title="Đã chuyển khoản VietQR">
                                            ĐÃ TT
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                            </td>

                            <!-- Recipient -->
                            <td class="py-4 px-4">
                                <div class="font-bold text-slate-800">{{ $order->receiver_name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $order->receiver_phone }}</div>
                                <p class="text-[11px] text-slate-400 truncate max-w-[200px]" title="{{ $order->shipping_address }}">
                                    {{ $order->shipping_address }}
                                </p>
                            </td>

                            <!-- Products preview -->
                            <td class="py-4 px-4">
                                <span class="font-bold text-slate-800">{{ $order->items->count() }} sản phẩm</span>
                                <div class="text-[11px] text-slate-500 truncate max-w-[220px] mt-0.5" title="{{ $order->items->pluck('product_name')->implode(', ') }}">
                                    {{ $order->items->first()?->product_name }}
                                    @if($order->items->count() > 1)
                                        <span class="text-primary font-semibold">(+{{ $order->items->count() - 1 }})</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Total Amount -->
                            <td class="py-4 px-4 text-right">
                                <div class="font-extrabold text-sm text-slate-900">
                                    {{ number_format($order->total_amount, 0, ',', '.') }} đ
                                </div>
                                @if($order->payment_method === 'bank_transfer')
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-primary-50 text-primary border border-primary/20 mt-0.5" title="Đã chuyển khoản VietQR">
                                        VietQR (Đã TT)
                                    </span>
                                @else
                                    <span class="text-[10px] text-slate-400 block mt-0.5">COD</span>
                                @endif
                            </td>

                            <!-- Order Status Badge -->
                            <td class="py-4 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $order->status_badge_class }}">
                                    {{ $order->status_label }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.orders.show', $order) }}" 
                                       class="p-2 rounded-xl bg-slate-100 hover:bg-primary hover:text-white text-slate-600 transition-colors"
                                       title="Xem chi tiết đơn hàng">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>

                                    <a href="{{ route('admin.orders.print', $order) }}" target="_blank"
                                       class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors"
                                       title="In phiếu giao hàng">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                </div>
                                <p class="font-bold text-slate-700">Không tìm thấy đơn hàng nào</p>
                                <p class="text-xs text-slate-400 mt-0.5">Thử thay đổi bộ lọc tìm kiếm hoặc từ khóa</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($orders->hasPages())
            <div class="p-6 border-t border-slate-100">
                {{ $orders->links() }}
            </div>
        @endif

    </div>

</div>

<script>
    function adminOrderManager() {
        return {
            selectedOrderIds: [],
            pageOrderIds: @json($orders->pluck('id')),

            get isAllSelected() {
                return this.pageOrderIds.length > 0 && 
                       this.pageOrderIds.every(id => this.selectedOrderIds.includes(id));
            },

            toggleSelectAll(e) {
                if (e.target.checked) {
                    this.selectedOrderIds = Array.from(new Set([...this.selectedOrderIds, ...this.pageOrderIds]));
                } else {
                    this.selectedOrderIds = this.selectedOrderIds.filter(id => !this.pageOrderIds.includes(id));
                }
            },

            clearSelection() {
                this.selectedOrderIds = [];
            },

            printSelected() {
                if (this.selectedOrderIds.length === 0) return;
                const url = '{{ route("admin.orders.print-bulk") }}?ids=' + this.selectedOrderIds.join(',');
                window.open(url, '_blank');
            },

            confirmAndPrintSelected() {
                if (this.selectedOrderIds.length === 0) return;

                // Create a form to submit POST with target="_blank"
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("admin.orders.bulk-action") }}';
                form.target = '_blank';

                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = '{{ csrf_token() }}';
                form.appendChild(tokenInput);

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'confirm_and_print';
                form.appendChild(actionInput);

                this.selectedOrderIds.forEach(id => {
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'order_ids[]';
                    idInput.value = id;
                    form.appendChild(idInput);
                });

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);

                // Reload the current page after a short delay to reflect confirmed status
                setTimeout(() => {
                    window.location.reload();
                }, 800);
            }
        };
    }
</script>
@endsection
