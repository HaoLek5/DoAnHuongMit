@extends('layouts.admin')

@section('title', 'Quản lý Nhập kho & Lịch sử phiếu')
@section('page_title', 'Quản lý Nhập kho')

@section('content')
<div x-data="stockReceiptManager()" x-init="init()" class="space-y-6">

    <!-- Search, Filters Bar & Create Button -->
    <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            
            <!-- Filter Form -->
            <form method="GET" action="{{ route('admin.stock-receipts.index') }}" class="flex-1 flex flex-wrap items-center gap-3">
                <!-- Search input -->
                <div class="relative flex-1 min-w-[240px]">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm theo mã phiếu (PN0001), nhà cung cấp, SĐT..." 
                           class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
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

                <!-- Status filter -->
                <div class="w-40">
                    <select name="status" 
                            class="w-full px-3 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-700 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                        <option value="">Tất cả trạng thái</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="updated" {{ request('status') === 'updated' ? 'selected' : '' }}>Đã chỉnh sửa</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>

                <!-- Filter Button -->
                <button type="submit" 
                        class="px-5 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Lọc
                </button>

                @if(request()->hasAny(['search', 'date_from', 'date_to', 'status']))
                    <a href="{{ route('admin.stock-receipts.index') }}" 
                       class="px-3.5 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium transition-all">
                        Đặt lại
                    </a>
                @endif
            </form>

            <!-- Create Stock Receipt Button -->
            <button @click="openCreateModal()" 
                    class="px-5 py-3 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-semibold text-xs tracking-wide shadow-lg shadow-primary/25 hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Nhập kho mới</span>
            </button>
        </div>
    </div>

    <!-- Stock Receipts Table -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-400 uppercase font-semibold">
                        <th class="py-4 px-4 w-12 text-center">#</th>
                        <th class="py-4 px-4 font-mono">Mã phiếu</th>
                        <th class="py-4 px-4 text-center">Ngày lập</th>
                        <th class="py-4 px-4 text-center">Người lập</th>
                        <th class="py-4 px-4 text-right w-28">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($receipts as $r)
                        <!-- Parent Receipt Row -->
                        <tr class="hover:bg-slate-50/80 transition-colors group cursor-pointer"
                            @click="toggleExpand({{ $r->id }})">
                            
                            <!-- Expand icon button -->
                            <td class="py-3.5 px-4 text-center">
                                <button type="button" class="w-6 h-6 rounded-lg bg-slate-100 group-hover:bg-primary/10 text-slate-400 group-hover:text-primary flex items-center justify-center transition-all"
                                        :class="{ 'rotate-90 bg-primary/10 text-primary': isExpanded({{ $r->id }}) }">
                                    <svg class="w-3.5 h-3.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </td>

                            <!-- Code (PN0001) -->
                            <td class="py-3.5 px-4 font-mono font-bold text-primary text-sm">
                                {{ $r->code }}
                            </td>

                            <!-- Receipt Date (Ngày lập) -->
                            <td class="py-3.5 px-4 text-center text-slate-600 font-medium">
                                {{ $r->receipt_date ? $r->receipt_date->format('d/m/Y') : '—' }}
                            </td>

                            <!-- Created By (Người lập) -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-100 text-slate-700 font-medium text-xs">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                    {{ $r->user->name ?? 'Admin' }}
                                </span>
                            </td>

                            <!-- Action Buttons -->
                            <td class="py-3.5 px-4 text-right" @click.stop>
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($r->status !== 'cancelled')
                                    <!-- Edit Receipt Button -->
                                    <button type="button" 
                                            @click="openEditModal({{ $r->id }})" 
                                            title="Sửa phiếu nhập kho"
                                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-primary hover:text-white text-slate-600 flex items-center justify-center transition-all shadow-xs">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <!-- Cancel Receipt Button -->
                                    <button type="button" 
                                            @click="cancelReceipt({{ $r->id }}, '{{ $r->code }}')" 
                                            title="Hủy phiếu nhập (hoàn trừ kho)"
                                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-rose-500 hover:text-white text-slate-600 flex items-center justify-center transition-all shadow-xs">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <!-- Child Items Sub-table (Accordion) -->
                        <tr x-show="isExpanded({{ $r->id }})" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="bg-slate-50/70 border-b border-slate-100">
                            <td colspan="5" class="p-0">
                                <div class="py-4 px-6 sm:px-12 bg-slate-50/90 border-y border-slate-200/60">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-primary"></span>
                                            <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider">
                                                Chi tiết các mặt hàng trong phiếu nhập [{{ $r->code }}]
                                            </h4>
                                            <span class="text-[11px] text-slate-400 font-medium">
                                                ({{ $r->items->count() }} dòng sản phẩm)
                                            </span>
                                        </div>
                                    </div>

                                    <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-xs">
                                        <table class="w-full text-left text-xs">
                                            <thead>
                                                <tr class="bg-slate-100/70 text-slate-500 uppercase font-semibold text-[11px] border-b border-slate-200/70">
                                                    <th class="py-2.5 px-4 w-12 text-center">STT</th>
                                                    <th class="py-2.5 px-3 w-16 text-center">Ảnh</th>
                                                    <th class="py-2.5 px-4">Sản phẩm & Biến thể</th>
                                                    <th class="py-2.5 px-4 text-center">Số lượng nhập</th>
                                                    <th class="py-2.5 px-4 text-right">Đơn giá nhập</th>
                                                    <th class="py-2.5 px-4 text-right">Thành tiền</th>
                                                    <th class="py-2.5 px-4">Ghi chú dòng</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($r->items as $idx => $item)
                                                    <tr class="hover:bg-slate-50/50">
                                                        <td class="py-2.5 px-4 text-center text-slate-400 font-medium">{{ $idx + 1 }}</td>
                                                        <td class="py-2.5 px-3 text-center">
                                                            <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden inline-flex items-center justify-center">
                                                                @if($item->product && $item->product->primary_image_url)
                                                                    <img src="{{ $item->product->primary_image_url }}" alt="" class="w-full h-full object-cover">
                                                                @else
                                                                    <span class="text-[10px] text-slate-400">HM</span>
                                                                @endif
                                                            </div>
                                                        </td>
                                                        <td class="py-2.5 px-4">
                                                            <span class="font-mono text-primary font-bold text-[11px]">{{ $item->product->code ?? '—' }}</span>
                                                            <div class="font-bold text-slate-800">{{ $item->product->name ?? 'Sản phẩm đã xóa' }}</div>
                                                            <div class="text-[11px] text-slate-500 mt-0.5">
                                                                Biến thể: <strong class="text-primary-700">{{ $item->variant->variant_title ?? 'Mặc định' }}</strong>
                                                            </div>
                                                        </td>
                                                        <td class="py-2.5 px-4 text-center">
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                                +{{ number_format($item->quantity) }}
                                                            </span>
                                                        </td>
                                                        <td class="py-2.5 px-4 text-right font-medium text-slate-700">
                                                            {{ number_format($item->unit_cost, 0, ',', '.') }} đ
                                                        </td>
                                                        <td class="py-2.5 px-4 text-right font-bold text-slate-900">
                                                            {{ number_format($item->total_cost, 0, ',', '.') }} đ
                                                        </td>
                                                        <td class="py-2.5 px-4 text-slate-400 text-[11px]">
                                                            {{ $item->notes ?? '—' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="bg-slate-50 border-t border-slate-200 font-bold text-slate-800">
                                                    <td colspan="3" class="py-2.5 px-4 text-right uppercase text-[11px]">Tổng cộng:</td>
                                                    <td class="py-2.5 px-4 text-center text-primary font-extrabold">{{ number_format($r->total_quantity) }} SP</td>
                                                    <td></td>
                                                    <td class="py-2.5 px-4 text-right text-primary font-extrabold">{{ number_format($r->total_amount, 0, ',', '.') }} đ</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400">
                                <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-600">Chưa có phiếu nhập kho nào</p>
                                <p class="text-xs text-slate-400 mt-1">Bấm "Nhập kho mới" để tạo phiếu nhập và tăng số lượng tồn kho sản phẩm.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($receipts->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $receipts->links() }}
            </div>
        @endif
    </div>

    <!-- ── POPUP MODAL: TẠO / SỬA PHIẾU NHẬP KHO ── -->
    <div x-show="formModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <!-- Backdrop -->
        <div x-show="formModalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeFormModal()"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="formModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-4xl border border-slate-100">

                <!-- Modal Header -->
                <div class="px-6 py-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-primary-300">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-white" x-text="isEditMode ? 'Chỉnh sửa phiếu nhập kho [' + form.code + ']' : 'Tạo phiếu nhập kho mới'"></h3>
                            <p class="text-xs text-slate-300" x-text="isEditMode ? 'Điều chỉnh số lượng và thông tin phiếu. Tồn kho sẽ được bù trừ tự động.' : 'Nhập hàng vào kho và tăng số lượng tồn kho tự động cho sản phẩm.'"></p>
                        </div>
                    </div>
                    <button type="button" @click="closeFormModal()" class="text-slate-400 hover:text-white transition-colors p-2 rounded-xl hover:bg-white/10">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body Form -->
                <form @submit.prevent="submitReceiptForm()" class="p-6 space-y-6">
                    
                    <!-- Top Info: Code, Supplier, Phone, Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                        
                        <!-- Receipt Code (Disabled) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Mã phiếu nhập</label>
                            <input type="text" x-model="form.code" disabled 
                                   class="w-full px-3 py-2 rounded-xl bg-slate-200 text-slate-600 font-mono font-bold text-xs border border-slate-300 cursor-not-allowed">
                        </div>

                        <!-- Supplier Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nhà cung cấp</label>
                            <input type="text" x-model="form.supplier_name" placeholder="vd: Vinamilk, FrieslandCampina..."
                                   class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none bg-white">
                        </div>

                        <!-- Supplier Phone -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Số điện thoại NCC</label>
                            <input type="text" x-model="form.supplier_phone" placeholder="vd: 0987654321"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none bg-white">
                        </div>

                        <!-- Receipt Date (Disabled / Readonly) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Ngày nhập kho</label>
                            <input type="date" x-model="form.receipt_date" disabled 
                                   class="w-full px-3 py-2 rounded-xl bg-slate-200 text-slate-600 font-mono font-medium text-xs border border-slate-300 cursor-not-allowed">
                        </div>

                        <!-- Notes Full Width -->
                        <div class="sm:col-span-4">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Ghi chú phiếu nhập</label>
                            <input type="text" x-model="form.notes" placeholder="vd: Nhập lô hàng đầu tháng 10, hóa đơn VAT số 00129..."
                                   class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none bg-white">
                        </div>
                    </div>

                    <!-- ── KIOT-STYLE PRODUCT SEARCH BAR & AUTOCOMPLETE ── -->
                    <div class="relative" @click.outside="showSuggestions = false">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                <span>Tìm & Thêm sản phẩm nhập kho (Kiểu Kiot)</span>
                                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                            </label>
                            <span class="text-[11px] text-slate-400">
                                Gõ mã SP (SP0001...) hoặc tên sản phẩm để chọn nhanh
                            </span>
                        </div>

                        <div class="relative flex items-center">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-primary">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text" 
                                   x-model="productSearchQuery" 
                                   @focus="if(productSearchQuery.trim()) showSuggestions = true"
                                   @input="onSearchInput()"
                                   @keydown.escape="showSuggestions = false"
                                   @keydown.arrow-down.prevent="navigateSuggestion(1)"
                                   @keydown.arrow-up.prevent="navigateSuggestion(-1)"
                                   @keydown.enter.prevent="selectHighlightedSuggestion()"
                                   placeholder="Gõ tên hoặc mã SP để gợi ý sản phẩm (vd: SP0001, Friso Gold, Bobby, Meiji, Vani...)" 
                                   class="w-full pl-11 pr-10 py-3 rounded-2xl border-2 border-primary-200 text-xs sm:text-sm font-medium focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all shadow-xs bg-white placeholder-slate-400">
                            
                            <!-- Clear input button -->
                            <button type="button" x-show="productSearchQuery.length > 0" 
                                    @click="productSearchQuery = ''; showSuggestions = false"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>

                        <!-- SUGGESTIONS LIST (KIOT AUTOCOMPLETE) -->
                        <div x-show="showSuggestions && filteredSuggestions.length > 0"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-slate-200 max-h-80 overflow-y-auto z-50 divide-y divide-slate-100">
                            
                            <template x-for="(v, sIdx) in filteredSuggestions" :key="v.variant_id">
                                <div @click="addVariantToReceipt(v)"
                                     @mouseenter="highlightedSuggestionIndex = sIdx"
                                     :class="{'bg-primary-50/80': highlightedSuggestionIndex === sIdx, 'hover:bg-slate-50': highlightedSuggestionIndex !== sIdx}"
                                     class="px-4 py-3 flex items-center justify-between gap-3 cursor-pointer transition-colors group">
                                    
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center flex-shrink-0">
                                            <template x-if="v.image_url">
                                                <img :src="v.image_url" alt="" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!v.image_url">
                                                <span class="text-[10px] font-bold text-slate-400">HM</span>
                                            </template>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono font-bold text-primary text-xs" x-text="v.product_code"></span>
                                                <span class="font-bold text-slate-800 text-xs truncate" x-text="v.product_name"></span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-semibold text-[11px]" x-text="'Biến thể: ' + v.variant_title"></span>
                                                <span class="text-[11px] text-slate-400" x-text="'Danh mục: ' + v.category_name"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right flex-shrink-0 flex items-center gap-4">
                                        <div>
                                            <div class="text-xs font-bold text-slate-700">
                                                Tồn kho: <span class="text-emerald-600 font-extrabold" x-text="v.stock_quantity"></span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 mt-0.5">
                                                Giá vốn: <span class="font-bold text-amber-700" x-text="formatMoney(v.cost_price) + ' đ'"></span>
                                            </div>
                                        </div>

                                        <span class="w-8 h-8 rounded-xl bg-primary-50 text-primary group-hover:bg-primary group-hover:text-white flex items-center justify-center transition-colors shadow-xs">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- No suggestions found -->
                        <div x-show="showSuggestions && productSearchQuery.trim() !== '' && filteredSuggestions.length === 0"
                             class="absolute left-0 right-0 mt-2 bg-white rounded-2xl shadow-xl border border-slate-200 p-6 text-center z-50 text-slate-400 text-xs">
                            Không tìm thấy sản phẩm nào khớp với từ khóa "<span class="font-bold text-slate-700" x-text="productSearchQuery"></span>"
                        </div>
                    </div>

                    <!-- ── SELECTED ITEMS TABLE (KIOT STYLE) ── -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Mặt hàng trong phiếu (<span x-text="form.items.length"></span> dòng)
                            </span>
                            <span x-show="form.items.length > 0" class="text-[11px] text-slate-400">
                                Bấm + hoặc - để điều chỉnh số lượng nhập
                            </span>
                        </div>

                        <!-- Empty state -->
                        <div x-show="form.items.length === 0" 
                             class="p-8 rounded-2xl border-2 border-dashed border-slate-200 text-center bg-slate-50/50">
                            <div class="w-12 h-12 rounded-2xl bg-primary-50 text-primary mx-auto mb-2 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <p class="text-xs font-bold text-slate-700">Chưa có sản phẩm nào trong phiếu nhập</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Gõ tên sản phẩm hoặc mã SP (ví dụ: SP0001, Friso...) vào thanh tìm kiếm phía trên để thêm hàng.</p>
                        </div>

                        <!-- Items Table -->
                        <div x-show="form.items.length > 0" class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-xs">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="bg-slate-100/80 text-slate-500 uppercase font-bold text-[11px] border-b border-slate-200">
                                        <th class="py-3 px-3 w-12 text-center">#</th>
                                        <th class="py-3 px-3 min-w-[240px]">Sản phẩm & Biến thể</th>
                                        <th class="py-3 px-3 w-24 text-center">Tồn kho</th>
                                        <th class="py-3 px-3 w-36 text-center">Số lượng nhập</th>
                                        <th class="py-3 px-3 w-36 text-right">Đơn giá nhập (đ)</th>
                                        <th class="py-3 px-3 w-36 text-right">Thành tiền (đ)</th>
                                        <th class="py-3 px-3 w-12 text-center">Xóa</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(item, index) in form.items" :key="item.product_variant_id">
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="py-3 px-3 text-center text-slate-400 font-bold" x-text="index + 1"></td>
                                            
                                            <!-- Product Info -->
                                            <td class="py-3 px-3">
                                                <div class="flex items-center gap-2.5">
                                                    <div class="w-9 h-9 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center flex-shrink-0">
                                                        <template x-if="item.image_url">
                                                            <img :src="item.image_url" alt="" class="w-full h-full object-cover">
                                                        </template>
                                                        <template x-if="!item.image_url">
                                                            <span class="text-[10px] font-bold text-slate-400">HM</span>
                                                        </template>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="font-mono text-primary font-bold text-[11px]" x-text="item.product_code"></span>
                                                            <span class="font-bold text-slate-800 text-xs truncate max-w-[220px]" x-text="item.product_name"></span>
                                                        </div>
                                                        <div class="text-[11px] text-slate-500">
                                                            Biến thể: <strong class="text-primary-700" x-text="item.variant_title"></strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="text" x-model="item.notes" placeholder="Ghi chú dòng này (nếu có)..."
                                                       class="w-full mt-1.5 px-2 py-1 rounded-lg border border-slate-100 text-[11px] text-slate-500 placeholder-slate-300 outline-none focus:border-primary">
                                            </td>

                                            <!-- Current Stock -->
                                            <td class="py-3 px-3 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold text-[11px] bg-slate-100 text-slate-600"
                                                      x-text="item.current_stock !== null ? item.current_stock : '—'">
                                                </span>
                                            </td>

                                            <!-- Quantity with [-] [+] Steppers -->
                                            <td class="py-3 px-3 text-center">
                                                <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white p-0.5 shadow-xs">
                                                    <button type="button" @click="decreaseQty(index)"
                                                            class="w-7 h-7 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 flex items-center justify-center font-bold text-xs transition-colors">
                                                        -
                                                    </button>
                                                    <input type="number" min="1" x-model.number="item.quantity" 
                                                           @input="calculateTotals()"
                                                           class="w-14 text-center font-extrabold text-slate-800 text-xs border-0 outline-none p-0 focus:ring-0">
                                                    <button type="button" @click="increaseQty(index)"
                                                            class="w-7 h-7 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 flex items-center justify-center font-bold text-xs transition-colors">
                                                        +
                                                    </button>
                                                </div>
                                            </td>

                                            <!-- Unit Cost Input -->
                                            <td class="py-3 px-3 text-right">
                                                <input type="number" min="0" step="1000" x-model.number="item.unit_cost" 
                                                       @input="calculateTotals()"
                                                       class="w-32 px-2.5 py-1.5 text-right font-semibold text-slate-800 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                                            </td>

                                            <!-- Total Row Cost -->
                                            <td class="py-3 px-3 text-right font-extrabold text-slate-900"
                                                x-text="formatMoney((item.quantity || 0) * (item.unit_cost || 0)) + ' đ'">
                                            </td>

                                            <!-- Delete Row Button -->
                                            <td class="py-3 px-3 text-center">
                                                <button type="button" @click="removeItemRow(index)" 
                                                        title="Xóa dòng"
                                                        class="w-7 h-7 rounded-xl text-slate-400 hover:text-rose-500 hover:bg-rose-50 flex items-center justify-center transition-colors">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>

                                <!-- Summary Row -->
                                <tfoot>
                                    <tr class="bg-slate-50 border-t border-slate-200 font-bold text-slate-800">
                                        <td colspan="3" class="py-3 px-4 text-right uppercase text-[11px]">Tổng cộng toàn phiếu:</td>
                                        <td class="py-3 px-3 text-center text-primary font-extrabold text-sm" x-text="computedTotalQuantity + ' SP'"></td>
                                        <td></td>
                                        <td class="py-3 px-3 text-right text-primary font-extrabold text-sm" x-text="formatMoney(computedTotalAmount) + ' đ'"></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="closeFormModal()"
                                class="px-5 py-2.5 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors">
                            Hủy bỏ
                        </button>
                        <button type="submit" :disabled="loading"
                                class="px-7 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-bold text-xs shadow-lg shadow-primary/25 hover:shadow-xl transition-all flex items-center gap-2 disabled:opacity-50">
                            <svg x-show="loading" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-text="isEditMode ? 'Cập nhật phiếu nhập' : 'Nhập kho'"></span>
                        </button>
                    </div>

                </form>
            </div>
    </div>

</div>

@push('scripts')
<script>
function stockReceiptManager() {
    return {
        expandedReceipts: [],
        selectableVariants: @json($selectableVariants),
        nextCode: @json($nextCode),

        // Modal states
        formModalOpen: false,
        isEditMode: false,
        loading: false,

        // Kiot Autocomplete states
        productSearchQuery: '',
        showSuggestions: false,
        highlightedSuggestionIndex: 0,

        // Form data
        form: {
            id: null,
            code: '',
            supplier_name: '',
            supplier_phone: '',
            receipt_date: new Date().toISOString().split('T')[0],
            notes: '',
            items: []
        },

        init() {
            // Không mở drop sẵn phiếu nào
            this.expandedReceipts = [];
        },

        toggleExpand(id) {
            if (this.expandedReceipts.includes(id)) {
                this.expandedReceipts = [];
            } else {
                this.expandedReceipts = [id]; // Close others, keep single expand
            }
        },

        isExpanded(id) {
            return this.expandedReceipts.includes(id);
        },

        // ── KIOT SEARCH & SUGGESTION METHODS ──
        get filteredSuggestions() {
            const q = this.productSearchQuery.trim().toLowerCase();
            if (!q) return [];
            return this.selectableVariants.filter(v => {
                const codeMatch = v.product_code && v.product_code.toLowerCase().includes(q);
                const nameMatch = v.product_name && v.product_name.toLowerCase().includes(q);
                const variantMatch = v.variant_title && v.variant_title.toLowerCase().includes(q);
                const categoryMatch = v.category_name && v.category_name.toLowerCase().includes(q);
                return codeMatch || nameMatch || variantMatch || categoryMatch;
            }).slice(0, 10);
        },

        onSearchInput() {
            this.highlightedSuggestionIndex = 0;
            this.showSuggestions = this.productSearchQuery.trim().length > 0;
        },

        navigateSuggestion(dir) {
            if (!this.showSuggestions || this.filteredSuggestions.length === 0) return;
            const len = this.filteredSuggestions.length;
            this.highlightedSuggestionIndex = (this.highlightedSuggestionIndex + dir + len) % len;
        },

        selectHighlightedSuggestion() {
            if (this.showSuggestions && this.filteredSuggestions.length > 0) {
                this.addVariantToReceipt(this.filteredSuggestions[this.highlightedSuggestionIndex]);
            }
        },

        addVariantToReceipt(v) {
            const existing = this.form.items.find(item => item.product_variant_id === v.variant_id);
            if (existing) {
                existing.quantity = (parseInt(existing.quantity) || 0) + 1;
            } else {
                this.form.items.push({
                    id: null,
                    product_id: v.product_id,
                    product_variant_id: v.variant_id,
                    product_code: v.product_code,
                    product_name: v.product_name,
                    variant_title: v.variant_title,
                    image_url: v.image_url,
                    current_stock: v.stock_quantity,
                    quantity: 1,
                    unit_cost: 0,
                    notes: ''
                });
            }
            this.productSearchQuery = '';
            this.showSuggestions = false;
        },

        increaseQty(index) {
            this.form.items[index].quantity = (parseInt(this.form.items[index].quantity) || 0) + 1;
        },

        decreaseQty(index) {
            const q = parseInt(this.form.items[index].quantity) || 0;
            if (q > 1) {
                this.form.items[index].quantity = q - 1;
            }
        },

        removeItemRow(index) {
            this.form.items.splice(index, 1);
        },

        openCreateModal() {
            this.isEditMode = false;
            this.productSearchQuery = '';
            this.showSuggestions = false;
            this.highlightedSuggestionIndex = 0;
            this.form = {
                id: null,
                code: this.nextCode,
                supplier_name: '',
                supplier_phone: '',
                receipt_date: new Date().toISOString().split('T')[0],
                notes: '',
                items: []
            };
            this.formModalOpen = true;
        },

        async openEditModal(id) {
            this.loading = true;
            this.productSearchQuery = '';
            this.showSuggestions = false;
            this.highlightedSuggestionIndex = 0;
            try {
                const res = await fetch(`/admin/stock-receipts/${id}`);
                const data = await res.json();
                if (data.success && data.receipt) {
                    const r = data.receipt;
                    this.isEditMode = true;
                    this.form = {
                        id: r.id,
                        code: r.code,
                        supplier_name: r.supplier_name,
                        supplier_phone: r.supplier_phone || '',
                        receipt_date: r.receipt_date ? r.receipt_date.split('T')[0] : '',
                        notes: r.notes || '',
                        items: r.items.map(item => {
                            const foundV = this.selectableVariants.find(v => v.variant_id == item.product_variant_id);
                            return {
                                id: item.id,
                                product_id: item.product_id,
                                product_variant_id: item.product_variant_id,
                                product_code: foundV ? foundV.product_code : (item.product ? item.product.code : '—'),
                                product_name: foundV ? foundV.product_name : (item.product ? item.product.name : 'Sản phẩm'),
                                variant_title: foundV ? foundV.variant_title : (item.variant ? item.variant.variant_title : 'Mặc định'),
                                image_url: foundV ? foundV.image_url : (item.product ? item.product.primary_image_url : ''),
                                current_stock: foundV ? foundV.stock_quantity : (item.variant ? item.variant.stock_quantity : 0),
                                quantity: parseInt(item.quantity) || 1,
                                unit_cost: parseFloat(item.unit_cost) || 0,
                                notes: item.notes || ''
                            };
                        })
                    };
                    this.formModalOpen = true;
                }
            } catch (err) {
                alert('Không thể tải thông tin phiếu nhập: ' + err.message);
            } finally {
                this.loading = false;
            }
        },

        closeFormModal() {
            this.formModalOpen = false;
            this.productSearchQuery = '';
            this.showSuggestions = false;
        },

        get computedTotalQuantity() {
            return this.form.items.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
        },

        get computedTotalAmount() {
            return this.form.items.reduce((sum, item) => sum + ((parseInt(item.quantity) || 0) * (parseFloat(item.unit_cost) || 0)), 0);
        },

        calculateTotals() {
            // reactivity handles this via getters
        },

        async submitReceiptForm() {
            // Validation
            if (this.form.items.length === 0) {
                alert('Vui lòng tìm và thêm ít nhất 1 sản phẩm vào phiếu nhập bằng ô tìm kiếm phía trên.');
                return;
            }
            for (let i = 0; i < this.form.items.length; i++) {
                const it = this.form.items[i];
                if (!it.product_variant_id) {
                    alert(`Dòng thứ ${i + 1}: Vui lòng chọn sản phẩm & biến thể.`);
                    return;
                }
                if (!it.quantity || it.quantity < 1) {
                    alert(`Sản phẩm [${it.product_code}]: Số lượng nhập phải lớn hơn hoặc bằng 1.`);
                    return;
                }
            }

            this.loading = true;
            const url = this.isEditMode ? `/admin/stock-receipts/${this.form.id}` : '/admin/stock-receipts';
            const method = this.isEditMode ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        supplier_name: this.form.supplier_name,
                        supplier_phone: this.form.supplier_phone,
                        receipt_date: this.form.receipt_date,
                        notes: this.form.notes,
                        items: this.form.items
                    })
                });

                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra khi lưu phiếu.');
                }
            } catch (err) {
                alert('Lỗi kết nối máy chủ: ' + err.message);
            } finally {
                this.loading = false;
            }
        },

        async cancelReceipt(id, code) {
            if (!confirm(`Bạn có chắc chắn muốn HỦY phiếu nhập [${code}] không?\n\nLƯU Ý: Toàn bộ số lượng tồn kho của các mặt hàng trong phiếu này sẽ bị trừ hoàn trả lại kho!`)) {
                return;
            }

            try {
                const res = await fetch(`/admin/stock-receipts/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert(data.message || 'Không thể hủy phiếu.');
                }
            } catch (err) {
                alert('Lỗi kết nối: ' + err.message);
            }
        },

        formatMoney(amount) {
            return new Intl.NumberFormat('vi-VN').format(Math.round(amount || 0));
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr);
            return d.toLocaleDateString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' });
        }
    };
}
</script>
@endpush
@endsection
