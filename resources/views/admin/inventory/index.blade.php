@extends('layouts.admin')

@section('title', 'Kiểm kho')
@section('page_title', 'Kiểm kho')

@section('content')
<div x-data="kiotInventoryManager()" x-init="init()" class="space-y-6">

    <!-- Filter & Search Bar -->
    <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
            
            <!-- Search Input -->
            <div class="relative flex-1 min-w-[240px]">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Tìm theo Mã kiểm kho (KK...), Mã hàng (SP...), Tên hàng, Ghi chú..." 
                       class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
            </div>

            <!-- Type Filter -->
            <div class="w-44">
                <select name="type" 
                        class="w-full px-3 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-700 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                    <option value="">Tất cả loại biến động</option>
                    <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Kiểm kho (adjustment)</option>
                    <option value="import" {{ request('type') === 'import' ? 'selected' : '' }}>Nhập kho (import)</option>
                    <option value="sale" {{ request('type') === 'sale' ? 'selected' : '' }}>Bán hàng (sale)</option>
                    <option value="return" {{ request('type') === 'return' ? 'selected' : '' }}>Trả hàng (return)</option>
                </select>
            </div>

            <!-- Reference Type Filter -->
            <div class="w-40">
                <select name="reference_type" 
                        class="w-full px-3 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-700 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                    <option value="">Tất cả chứng từ</option>
                    <option value="purchase_receipt" {{ request('reference_type') === 'purchase_receipt' ? 'selected' : '' }}>Phiếu nhập</option>
                    <option value="order" {{ request('reference_type') === 'order' ? 'selected' : '' }}>Đơn bán hàng</option>
                    <option value="manual" {{ request('reference_type') === 'manual' ? 'selected' : '' }}>Thủ công</option>
                </select>
            </div>

            <!-- Date from -->
            <div class="w-36">
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       title="Từ ngày"
                       class="w-full px-3 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-700 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
            </div>

            <!-- Date to -->
            <div class="w-36">
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       title="Đến ngày"
                       class="w-full px-3 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-700 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
            </div>

            <!-- Filter Buttons -->
            <button type="submit" 
                    class="px-5 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-sm transition-all flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Lọc
            </button>

            @if(request()->hasAny(['search', 'type', 'reference_type', 'date_from', 'date_to']))
                <a href="{{ route('admin.inventory.index') }}" 
                   class="px-3.5 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium text-center transition-all">
                    Đặt lại
                </a>
            @endif
        </form>
    </div>

    <!-- Kiot-Style Inventory Audit Table (Ảnh 2 Kiot) -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50/90 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                        <!-- Checkbox -->
                        <th class="py-3.5 px-3 w-10 text-center">
                            <input type="checkbox" @change="toggleSelectAll($event)" class="rounded border-slate-300 text-primary focus:ring-primary/20">
                        </th>
                        <!-- Star -->
                        <th class="py-3.5 px-2 w-8 text-center">
                            <span class="text-slate-300 text-sm">&#9734;</span>
                        </th>
                        <!-- Mã kiểm kho -->
                        <th class="py-3.5 px-4 font-mono">Mã kiểm kho</th>
                        <!-- Thời gian -->
                        <th class="py-3.5 px-4 text-center">
                            <div class="inline-flex items-center gap-1">
                                <span>Thời gian</span>
                                <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </th>
                        <!-- Tổng chênh lệch -->
                        <th class="py-3.5 px-4 text-center">Tổng chênh lệch</th>
                        <!-- SL lệch tăng -->
                        <th class="py-3.5 px-4 text-center">SL lệch tăng</th>
                        <!-- SL lệch giảm -->
                        <th class="py-3.5 px-4 text-center">SL lệch giảm</th>
                        <!-- Ngày cân bằng -->
                        <th class="py-3.5 px-4 text-center">Ngày cân bằng</th>
                        <!-- SL thực tế -->
                        <th class="py-3.5 px-4 text-center">SL thực tế</th>
                        <!-- Ghi chú -->
                        <th class="py-3.5 px-4">Ghi chú</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $tx)
                        @php
                            $code = $tx->code;
                            $diff = $tx->quantity;
                            $inc = $tx->inc_quantity;
                            $dec = $tx->dec_quantity;
                            $actual = $tx->actual_stock;
                            $system = $tx->system_stock;
                            $prod = $tx->variant->product ?? null;
                            $prodCode = $prod->code ?? ('SP' . str_pad($tx->variant_id, 4, '0', STR_PAD_LEFT));
                            $prodName = $prod ? $prod->name : 'Sản phẩm đã xóa';
                            if ($tx->variant && $tx->variant->variant_title && $tx->variant->variant_title !== 'Mặc định') {
                                $prodName .= ' - ' . $tx->variant->variant_title;
                            }
                        @endphp

                        <!-- Parent Row (Click to Expand Detail) -->
                        <tr class="hover:bg-slate-50/80 transition-colors group cursor-pointer border-b border-slate-100"
                            :class="{ 'bg-blue-50/30': isExpanded({{ $tx->id }}) }"
                            @click="toggleExpand({{ $tx->id }})">
                            
                            <!-- Checkbox -->
                            <td class="py-3 px-3 text-center" @click.stop>
                                <input type="checkbox" :value="{{ $tx->id }}" x-model="selectedIds" class="rounded border-slate-300 text-primary focus:ring-primary/20">
                            </td>

                            <!-- Star Icon -->
                            <td class="py-3 px-2 text-center" @click.stop="toggleStar({{ $tx->id }})">
                                <button type="button" class="text-slate-300 hover:text-amber-400 transition-colors"
                                        :class="{ 'text-amber-400': starredIds.includes({{ $tx->id }}) }">
                                    <span class="text-sm" x-text="starredIds.includes({{ $tx->id }}) ? '★' : '☆'"></span>
                                </button>
                            </td>

                            <!-- Mã kiểm kho (KK000816) -->
                            <td class="py-3 px-4 font-mono font-bold text-primary hover:underline text-xs">
                                {{ $code }}
                            </td>

                            <!-- Thời gian -->
                            <td class="py-3 px-4 text-center text-slate-600 font-medium">
                                {{ $tx->created_at->format('d/m/Y H:i') }}
                            </td>

                            <!-- Tổng chênh lệch -->
                            <td class="py-3 px-4 text-center font-bold">
                                @if($diff > 0)
                                    <span class="text-emerald-600">+{{ $diff }}</span>
                                @elseif($diff < 0)
                                    <span class="text-rose-600">{{ $diff }}</span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>

                            <!-- SL lệch tăng -->
                            <td class="py-3 px-4 text-center font-medium">
                                <span class="{{ $inc > 0 ? 'text-emerald-600 font-bold' : 'text-slate-400' }}">{{ $inc }}</span>
                            </td>

                            <!-- SL lệch giảm -->
                            <td class="py-3 px-4 text-center font-medium">
                                <span class="{{ $dec > 0 ? 'text-rose-600 font-bold' : 'text-slate-400' }}">{{ $dec }}</span>
                            </td>

                            <!-- Ngày cân bằng -->
                            <td class="py-3 px-4 text-center text-slate-600 font-medium">
                                {{ $tx->created_at->format('d/m/Y H:i') }}
                            </td>

                            <!-- SL thực tế -->
                            <td class="py-3 px-4 text-center font-bold text-slate-800">
                                {{ $actual }}
                            </td>

                            <!-- Ghi chú -->
                            <td class="py-3 px-4 text-slate-600 text-xs truncate max-w-xs" title="{{ $tx->note }}">
                                {{ $tx->note ?? 'Phiếu kiểm kho được tạo tự động khi cập nhật Hàng hóa ' . $prodCode }}
                            </td>
                        </tr>

                        <!-- Expandable Detail Drawer (Khung chi tiết giống ảnh 2 Kiot) -->
                        <tr x-show="isExpanded({{ $tx->id }})" 
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="bg-white border-b-2 border-slate-200">
                            <td colspan="10" class="p-0">
                                <div class="bg-slate-50/70 border-y border-slate-200/80 p-6 space-y-4">
                                    
                                    <!-- Detail Tabs (Thông tin) -->
                                    <div class="border-b border-slate-200 flex items-center gap-6">
                                        <button type="button" class="border-b-2 border-primary text-primary font-bold text-xs pb-2 px-1 focus:outline-none">
                                            Thông tin
                                        </button>
                                    </div>

                                    <!-- Audit Ticket Header -->
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                        <div class="flex items-center gap-3">
                                            <h3 class="font-mono font-extrabold text-base text-slate-900 tracking-tight">
                                                {{ $code }}
                                            </h3>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                Đã cân bằng kho
                                            </span>
                                        </div>
                                    </div>

                                    <!-- User Meta Info -->
                                    <div class="text-xs text-slate-600 space-y-1 bg-white p-3.5 rounded-xl border border-slate-200/80">
                                        <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
                                            <span><strong>Người tạo:</strong> {{ $tx->creator->name ?? 'Admin' }}</span>
                                            <span class="text-slate-300">|</span>
                                            <span><strong>Ngày tạo:</strong> {{ $tx->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-x-6 gap-y-1">
                                            <span><strong>Người cân bằng:</strong> {{ $tx->creator->name ?? 'Admin' }}</span>
                                            <span class="text-slate-300">|</span>
                                            <span><strong>Ngày cân bằng:</strong> {{ $tx->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                    </div>

                                    <!-- Sub-table: Mặt hàng kiểm kê -->
                                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
                                        <table class="w-full text-left text-xs">
                                            <thead>
                                                <tr class="bg-slate-100/70 text-slate-700 font-bold border-b border-slate-200">
                                                    <th class="py-2.5 px-4 w-44 font-mono">Mã hàng</th>
                                                    <th class="py-2.5 px-4">Tên hàng</th>
                                                    <th class="py-2.5 px-4 text-center w-32">Tồn kho</th>
                                                    <th class="py-2.5 px-4 text-center w-32">Thực tế</th>
                                                    <th class="py-2.5 px-4 text-center w-32">SL lệch</th>
                                                </tr>
                                                <!-- Mini filter header row (like Kiot image 2) -->
                                                <tr class="bg-slate-50/50 border-b border-slate-200 text-slate-400">
                                                    <td class="py-1.5 px-4">
                                                        <input type="text" placeholder="Tìm mã hàng" disabled class="w-full px-2 py-1 rounded-lg border border-slate-200 bg-white text-[11px] outline-none">
                                                    </td>
                                                    <td class="py-1.5 px-4">
                                                        <input type="text" placeholder="Tìm tên hàng" disabled class="w-full px-2 py-1 rounded-lg border border-slate-200 bg-white text-[11px] outline-none">
                                                    </td>
                                                    <td colspan="3"></td>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <tr class="hover:bg-slate-50/50">
                                                    <!-- Mã hàng -->
                                                    <td class="py-3 px-4 font-mono font-bold text-primary">
                                                        {{ $prodCode }}
                                                    </td>

                                                    <!-- Tên hàng -->
                                                    <td class="py-3 px-4">
                                                        <div class="font-bold text-slate-800 text-xs">{{ $prodName }}</div>
                                                        @if($tx->variant && $tx->variant->product && $tx->variant->product->category)
                                                            <div class="text-[11px] text-slate-400 mt-0.5">
                                                                Danh mục: {{ $tx->variant->product->category->name }}
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <!-- Tồn kho (hệ thống trước khi cân bằng) -->
                                                    <td class="py-3 px-4 text-center font-semibold text-slate-600">
                                                        {{ $system }}
                                                    </td>

                                                    <!-- Thực tế -->
                                                    <td class="py-3 px-4 text-center font-bold text-slate-900">
                                                        {{ $actual }}
                                                    </td>

                                                    <!-- SL lệch -->
                                                    <td class="py-3 px-4 text-center font-extrabold">
                                                        @if($diff > 0)
                                                            <span class="text-emerald-600">+{{ $diff }}</span>
                                                        @elseif($diff < 0)
                                                            <span class="text-rose-600">{{ $diff }}</span>
                                                        @else
                                                            <span class="text-slate-400">0</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Bottom Row: Ghi chú (trái) & Tổng kết lệch (phải) -->
                                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-6 pt-2">
                                        <!-- Ghi chú box bên trái -->
                                        <div class="flex-1 max-w-xl">
                                            <div class="p-3.5 bg-white border border-slate-200 rounded-xl text-xs text-slate-700 shadow-xs leading-relaxed">
                                                {{ $tx->note ?? 'Phiếu kiểm kho được tạo tự động khi cập nhật Hàng hóa ' . $prodCode }}
                                            </div>
                                        </div>

                                        <!-- Summary Box bên phải giống Kiot -->
                                        <div class="w-full sm:w-64 space-y-1.5 text-xs text-right bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
                                            <div class="flex justify-between items-center text-slate-600">
                                                <span>Tổng thực tế:</span>
                                                <strong class="text-slate-800 text-sm font-bold">({{ $actual }})</strong>
                                            </div>
                                            <div class="flex justify-between items-center text-slate-600">
                                                <span>Tổng lệch tăng:</span>
                                                <strong class="text-emerald-600 text-sm font-bold">({{ $inc }})</strong>
                                            </div>
                                            <div class="flex justify-between items-center text-slate-600">
                                                <span>Tổng lệch giảm:</span>
                                                <strong class="text-rose-600 text-sm font-bold">({{ $dec }})</strong>
                                            </div>
                                            <div class="pt-1.5 border-t border-slate-200 flex justify-between items-center">
                                                <span class="font-bold text-slate-800">Tổng chênh lệch:</span>
                                                <strong class="text-primary text-sm font-extrabold">({{ $diff > 0 ? ('+' . $diff) : $diff }})</strong>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-400">
                                <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-600">Chưa có phiếu kiểm kho nào</p>
                                <p class="text-xs text-slate-400 mt-1">Khi hàng hóa được điều chỉnh tồn hoặc nhập kho, các phiếu kiểm kho sẽ hiển thị ở đây.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-white">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
function kiotInventoryManager() {
    return {
        expandedIds: [],
        selectedIds: [],
        starredIds: [],

        init() {
            // Không mở drop sẵn phiếu nào
            this.expandedIds = [];
        },

        toggleExpand(id) {
            if (this.expandedIds.includes(id)) {
                this.expandedIds = [];
            } else {
                this.expandedIds = [id]; // Close other rows to keep clean accordion view like Kiot
            }
        },

        isExpanded(id) {
            return this.expandedIds.includes(id);
        },

        toggleSelectAll(e) {
            if (e.target.checked) {
                this.selectedIds = @json($transactions->pluck('id'));
            } else {
                this.selectedIds = [];
            }
        },

        toggleStar(id) {
            if (this.starredIds.includes(id)) {
                this.starredIds = this.starredIds.filter(i => i !== id);
            } else {
                this.starredIds.push(id);
            }
        }
    };
}
</script>
@endpush
@endsection
