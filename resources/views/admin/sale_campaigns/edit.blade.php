@extends('layouts.admin')

@section('title', 'Chỉnh sửa Chiến dịch Sale - Hương Mít')
@section('page_title', 'Chỉnh sửa chiến dịch sale')

@section('content')
<div class="space-y-6" x-data="saleCampaignEditForm()">

    {{-- Breadcrumb & Back --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.sale-campaigns.index') }}" 
           class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-primary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Quay lại danh sách chiến dịch</span>
        </a>

        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400 font-semibold">Trạng thái:</span>
            <span class="px-3 py-1 rounded-full text-[11px] font-bold border"
                  :class="campaignStatusClass"
                  x-text="campaignStatusLabel"></span>
        </div>
    </div>

    {{-- Error Banner --}}
    <div x-show="errorMessage" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800 flex items-center gap-2.5 shadow-xs" style="display: none;">
        <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span x-text="errorMessage"></span>
    </div>

    <form @submit.prevent="submitForm">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            {{-- Left: Campaign General Info (4 cols) --}}
            <div class="lg:col-span-4 bg-white rounded-3xl border border-slate-100 p-6 shadow-sm space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-sm">Thông tin chiến dịch</h3>
                    <p class="text-[11px] text-slate-400">Cập nhật thời gian hoặc điều chỉnh quy mô sale</p>
                </div>

                {{-- Campaign Name --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Tên chiến dịch <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="form.name" required
                           placeholder="VD: Flash Sale Giờ Vàng 11.11"
                           class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-800 font-medium focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Mô tả / Ghi chú</label>
                    <textarea x-model="form.description" rows="3"
                              placeholder="Mô tả quyền lợi hoặc ghi chú nội bộ về chiến dịch..."
                              class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-800 font-medium focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"></textarea>
                </div>

                {{-- Time Range --}}
                <div class="space-y-4 pt-1 border-t border-slate-100">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-700">Thời gian bắt đầu <span class="text-rose-500">*</span></label>
                            <button type="button" @click="setStartNow()" class="text-[10px] font-bold text-primary hover:underline">
                                Bắt đầu ngay
                            </button>
                        </div>
                        <input type="datetime-local" x-model="form.start_time" required
                               class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-800 font-medium focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-bold text-slate-700">Thời gian kết thúc <span class="text-rose-500">*</span></label>
                            <div class="flex items-center gap-1.5 text-[10px] font-bold text-primary">
                                <button type="button" @click="addHoursToEnd(2)" class="hover:underline">+2h</button>
                                <span>•</span>
                                <button type="button" @click="addDaysToEnd(1)" class="hover:underline">+1 ngày</button>
                                <span>•</span>
                                <button type="button" @click="addDaysToEnd(3)" class="hover:underline">+3 ngày</button>
                            </div>
                        </div>
                        <input type="datetime-local" x-model="form.end_time" required
                               class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-800 font-medium focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                    </div>
                </div>

                {{-- Active Status Toggle --}}
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-700 block">Kích hoạt chiến dịch</span>
                        <span class="text-[11px] text-slate-400 block">Tự động mở bán khi đến giờ bắt đầu</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                </div>
            </div>

            {{-- Right: Products in Campaign (8 cols) --}}
            <div class="lg:col-span-8 bg-white rounded-3xl border border-slate-100 p-6 shadow-sm space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">
                            Sản phẩm tham gia Sale (<span x-text="items.length"></span>)
                        </h3>
                        <p class="text-[11px] text-slate-400">Tùy ý đặt giá sale và giới hạn số lượng sản phẩm mở bán</p>
                    </div>

                    {{-- Add Product Button --}}
                    <button type="button" @click="openProductPicker()"
                            class="px-4 py-2 rounded-2xl bg-primary text-white text-xs font-bold hover:bg-primary-600 shadow-xs flex items-center gap-1.5 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Thêm sản phẩm sale</span>
                    </button>
                </div>

                {{-- Empty State --}}
                <div x-show="items.length === 0" class="py-12 text-center text-slate-400 border-2 border-dashed border-slate-200 rounded-3xl">
                    <div class="w-12 h-12 mx-auto mb-2 rounded-2xl bg-primary-50 text-primary flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <div class="font-bold text-slate-700 text-xs mb-1">Chưa có sản phẩm nào được chọn</div>
                    <p class="text-[11px] text-slate-400 mb-3">Bấm nút "Thêm sản phẩm sale" ở trên để chọn các mặt hàng giảm giá.</p>
                    <button type="button" @click="openProductPicker()" class="px-4 py-2 rounded-xl border border-primary text-primary text-xs font-bold hover:bg-primary-50">
                        + Chọn sản phẩm ngay
                    </button>
                </div>

                {{-- Items Table --}}
                <div x-show="items.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200 shadow-xs">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold text-[10px] uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-3.5">Mặt hàng & Phân loại</th>
                                <th class="py-3 px-3 text-center">Tồn kho</th>
                                <th class="py-3 px-3 text-right">Giá gốc</th>
                                <th class="py-3 px-3 w-36 text-left">Giá Sale (VNĐ)</th>
                                <th class="py-3 px-3 w-28 text-center">Số lượng Sale</th>
                                <th class="py-3 px-3 text-center">Đã bán</th>
                                <th class="py-3 px-2 text-center w-12">Xóa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(it, idx) in items" :key="it.variant_id">
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    {{-- Product Info --}}
                                    <td class="py-3 px-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                                <template x-if="it.image_url">
                                                    <img :src="it.image_url" :alt="it.product_name" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!it.image_url">
                                                    <span class="text-[9px] font-bold text-slate-400">NO IMG</span>
                                                </template>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-xs line-clamp-1" x-text="it.product_name"></div>
                                                <div class="text-[11px] text-primary font-semibold flex items-center gap-1.5">
                                                    <span x-text="it.variant_title"></span>
                                                    <span class="text-slate-300">•</span>
                                                    <span class="text-slate-400 font-mono text-[10px]" x-text="it.code"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Stock --}}
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-lg text-[11px] font-bold"
                                              :class="it.stock_quantity > 0 ? 'bg-slate-100 text-slate-700' : 'bg-rose-100 text-rose-700'"
                                              x-text="it.stock_quantity + ' cái'"></span>
                                    </td>

                                    {{-- Original Price --}}
                                    <td class="py-3 px-3 text-right whitespace-nowrap font-medium text-slate-500">
                                        <span x-text="formatCurrency(it.original_price)"></span>
                                    </td>

                                    {{-- Sale Price Input --}}
                                    <td class="py-3 px-3">
                                        <div>
                                            <input type="text" 
                                                   :value="formatNumber(it.sale_price)" 
                                                   @input="onSalePriceInput(it, $event)"
                                                   placeholder="VD: 350.000"
                                                   class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs font-extrabold text-primary outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                            
                                            {{-- Discount badge --}}
                                            <div class="flex items-center justify-between text-[10px] mt-1">
                                                <span class="text-emerald-600 font-bold" x-show="calcDiscount(it) > 0">
                                                    Giảm <span x-text="calcDiscount(it)"></span>%
                                                </span>
                                                <span class="text-rose-500 font-bold" x-show="it.sale_price >= it.original_price">
                                                    Giá sale phải nhỏ hơn giá gốc
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Sale Quantity Input --}}
                                    <td class="py-3 px-3">
                                        <div>
                                            <input type="number" x-model.number="it.sale_quantity" min="1" :max="it.stock_quantity"
                                                   placeholder="10"
                                                   class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-center text-slate-800 outline-none focus:ring-1 focus:ring-primary focus:border-primary">
                                            <div class="text-[10px] text-slate-400 text-center mt-1">
                                                Tối đa: <strong x-text="it.stock_quantity"></strong>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Sold Quantity --}}
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-lg text-[11px] font-bold bg-primary-50 text-primary"
                                              x-text="(it.sold_quantity || 0) + ' suất'"></span>
                                    </td>

                                    {{-- Remove --}}
                                    <td class="py-3 px-2 text-center">
                                        <button type="button" @click="removeItem(idx)" 
                                                title="Xóa mặt hàng này khỏi chiến dịch"
                                                class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-500 hover:bg-rose-50 flex items-center justify-center transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Submit Actions --}}
                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('admin.sale-campaigns.index') }}" 
                       class="px-5 py-2.5 rounded-2xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition-all">
                        Hủy bỏ
                    </a>

                    <button type="submit" :disabled="isSubmitting || items.length === 0"
                            class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white text-xs font-bold shadow-md shadow-primary/20 transition-all disabled:opacity-50">
                        <span x-show="!isSubmitting">Cập nhật chiến dịch sale</span>
                        <span x-show="isSubmitting">Đang lưu dữ liệu...</span>
                    </button>
                </div>
            </div>

        </div>
    </form>

    {{-- Product Picker Modal --}}
    <div x-show="pickerModalOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;">
        
        <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-100 overflow-hidden flex flex-col max-h-[85vh]"
             @click.away="pickerModalOpen = false">
            
            {{-- Modal Header --}}
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 text-base">Chọn sản phẩm tham gia sale</h3>
                    <p class="text-xs text-slate-400">Chọn các phân loại sản phẩm để thiết lập giá và số lượng sale</p>
                </div>
                <button type="button" @click="pickerModalOpen = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Search bar in Modal --}}
            <div class="px-6 pt-4 pb-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" x-model="pickerSearch" placeholder="Tìm theo tên sản phẩm hoặc mã..." 
                           class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                </div>
            </div>

            {{-- Products & Variants List in Modal --}}
            <div class="p-6 overflow-y-auto space-y-3 flex-1">
                <template x-for="p in filteredCatalog" :key="p.id">
                    <div class="border border-slate-200/80 rounded-2xl p-3.5 space-y-2 hover:border-primary-200 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 overflow-hidden flex items-center justify-center flex-shrink-0">
                                <template x-if="p.primary_image_url">
                                    <img :src="p.primary_image_url" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!p.primary_image_url">
                                    <span class="text-[9px] font-bold text-slate-400">NO IMG</span>
                                </template>
                            </div>
                            <div class="flex-1">
                                <div class="font-bold text-slate-800 text-xs" x-text="p.name"></div>
                                <div class="text-[10px] text-slate-400">
                                    Mã: <span class="font-mono" x-text="p.code"></span> • Danh mục: <span x-text="p.category?.name || 'Chung'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Variants list of this product --}}
                        <div class="grid grid-cols-1 gap-1.5 pt-2 border-t border-slate-100">
                            <template x-for="v in p.variants" :key="v.id">
                                <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                                    <div>
                                        <span class="font-bold text-slate-700" x-text="v.variant_title || 'Mặc định'"></span>
                                        <div class="text-[11px] text-slate-500">
                                            Giá gốc: <strong class="text-slate-800" x-text="formatCurrency(v.price)"></strong>
                                            <span class="text-slate-300 mx-1">•</span>
                                            Kho: <strong :class="v.stock_quantity > 0 ? 'text-emerald-600' : 'text-rose-500'" x-text="v.stock_quantity + ' cái'"></strong>
                                        </div>
                                    </div>

                                    <div>
                                        <button type="button" 
                                                @click="addVariantToCampaign(p, v)"
                                                :disabled="isVariantAdded(v.id)"
                                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                                                :class="isVariantAdded(v.id) ? 'bg-slate-200 text-slate-400 cursor-not-allowed' : 'bg-primary text-white hover:bg-primary-600 shadow-xs'">
                                            <span x-text="isVariantAdded(v.id) ? '✓ Đã chọn' : '+ Thêm'"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer --}}
            <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-end">
                <button type="button" @click="pickerModalOpen = false" class="px-5 py-2 rounded-xl bg-slate-800 text-white text-xs font-bold hover:bg-slate-900 transition-all">
                    Xong
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function saleCampaignEditForm() {
    const rawCampaign = @json($campaign);
    const rawProducts = @json($products);

    const pad = (n) => n < 10 ? '0' + n : n;
    const formatDt = (dtStr) => {
        if (!dtStr) return '';
        const d = new Date(dtStr);
        return d.getFullYear() + '-' +
               pad(d.getMonth() + 1) + '-' +
               pad(d.getDate()) + 'T' +
               pad(d.getHours()) + ':' +
               pad(d.getMinutes());
    };

    const initialItems = (rawCampaign.items || []).map(it => {
        return {
            product_id: it.product_id,
            variant_id: it.product_variant_id,
            product_name: it.product?.name || 'Sản phẩm',
            variant_title: it.variant?.variant_title || 'Mặc định',
            code: it.product?.code || '',
            image_url: it.product?.primary_image_url || '',
            original_price: parseFloat(it.original_price) || 0,
            sale_price: parseFloat(it.sale_price) || 0,
            sale_quantity: parseInt(it.sale_quantity, 10) || 1,
            sold_quantity: parseInt(it.sold_quantity, 10) || 0,
            stock_quantity: it.variant?.stock_quantity || 0,
        };
    });

    return {
        catalog: rawProducts,
        pickerModalOpen: false,
        pickerSearch: '',
        isSubmitting: false,
        errorMessage: '',

        form: {
            id: rawCampaign.id,
            name: rawCampaign.name || '',
            description: rawCampaign.description || '',
            start_time: formatDt(rawCampaign.start_time),
            end_time: formatDt(rawCampaign.end_time),
            is_active: Boolean(rawCampaign.is_active),
        },

        items: initialItems,

        get campaignStatusLabel() {
            if (!this.form.is_active) return 'Đang tạm dừng';
            const now = new Date();
            const start = new Date(this.form.start_time);
            const end = new Date(this.form.end_time);
            if (now < start) return 'Sắp diễn ra';
            if (now > end) return 'Đã kết thúc';
            return '🔥 Đang diễn ra';
        },

        get campaignStatusClass() {
            if (!this.form.is_active) return 'bg-rose-100 text-rose-800 border-rose-200';
            const now = new Date();
            const start = new Date(this.form.start_time);
            const end = new Date(this.form.end_time);
            if (now < start) return 'bg-blue-100 text-blue-800 border-blue-200';
            if (now > end) return 'bg-slate-100 text-slate-700 border-slate-200';
            return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        },

        formatLocalDatetime(date) {
            return date.getFullYear() + '-' +
                   pad(date.getMonth() + 1) + '-' +
                   pad(date.getDate()) + 'T' +
                   pad(date.getHours()) + ':' +
                   pad(date.getMinutes());
        },

        setStartNow() {
            const now = new Date();
            this.form.start_time = this.formatLocalDatetime(now);
        },

        addHoursToEnd(h) {
            const start = this.form.start_time ? new Date(this.form.start_time) : new Date();
            const end = new Date(start.getTime() + h * 60 * 60 * 1000);
            this.form.end_time = this.formatLocalDatetime(end);
        },

        addDaysToEnd(d) {
            const start = this.form.start_time ? new Date(this.form.start_time) : new Date();
            const end = new Date(start.getTime() + d * 24 * 60 * 60 * 1000);
            this.form.end_time = this.formatLocalDatetime(end);
        },

        formatCurrency(num) {
            return new Intl.NumberFormat('vi-VN').format(num || 0) + ' đ';
        },

        formatNumber(num) {
            if (!num && num !== 0) return '';
            return new Intl.NumberFormat('vi-VN').format(num);
        },

        parseNumber(val) {
            if (!val) return 0;
            return parseInt(String(val).replace(/\./g, '').replace(/,/g, ''), 10) || 0;
        },

        onSalePriceInput(item, event) {
            const rawVal = this.parseNumber(event.target.value);
            item.sale_price = rawVal;
            event.target.value = this.formatNumber(rawVal);
        },

        calcDiscount(item) {
            if (item.original_price > 0 && item.original_price > item.sale_price) {
                return Math.round(((item.original_price - item.sale_price) / item.original_price) * 100);
            }
            return 0;
        },

        get filteredCatalog() {
            if (!this.pickerSearch.trim()) return this.catalog;
            const q = this.pickerSearch.toLowerCase();
            return this.catalog.filter(p => {
                return (p.name && p.name.toLowerCase().includes(q)) ||
                       (p.code && p.code.toLowerCase().includes(q));
            });
        },

        openProductPicker() {
            this.pickerSearch = '';
            this.pickerModalOpen = true;
        },

        isVariantAdded(variantId) {
            return this.items.some(it => it.variant_id === variantId);
        },

        addVariantToCampaign(product, variant) {
            if (this.isVariantAdded(variant.id)) return;

            const origPrice = parseFloat(variant.price) || 0;
            const defaultSalePrice = Math.max(1000, Math.round(origPrice * 0.85 / 1000) * 1000);
            const defaultQty = Math.max(1, Math.min(variant.stock_quantity || 10, 20));

            this.items.push({
                product_id: product.id,
                variant_id: variant.id,
                product_name: product.name,
                variant_title: variant.variant_title || 'Mặc định',
                code: product.code,
                image_url: product.primary_image_url || '',
                original_price: origPrice,
                sale_price: defaultSalePrice,
                sale_quantity: defaultQty,
                sold_quantity: 0,
                stock_quantity: variant.stock_quantity || 0,
            });
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        async submitForm() {
            this.errorMessage = '';

            if (!this.form.name.trim()) {
                this.errorMessage = 'Vui lòng nhập tên chiến dịch sale.';
                return;
            }

            if (!this.form.start_time || !this.form.end_time) {
                this.errorMessage = 'Vui lòng chọn đầy đủ thời gian bắt đầu và kết thúc.';
                return;
            }

            if (new Date(this.form.end_time) <= new Date(this.form.start_time)) {
                this.errorMessage = 'Thời gian kết thúc phải diễn ra sau thời gian bắt đầu.';
                return;
            }

            if (this.items.length === 0) {
                this.errorMessage = 'Vui lòng chọn ít nhất 1 sản phẩm tham gia chiến dịch sale.';
                return;
            }

            for (const it of this.items) {
                if (it.sale_price <= 0) {
                    this.errorMessage = `Vui lòng nhập giá sale hợp lệ cho sản phẩm "${it.product_name}".`;
                    return;
                }
                if (it.sale_price >= it.original_price) {
                    this.errorMessage = `Giá sale (${this.formatCurrency(it.sale_price)}) phải nhỏ hơn giá gốc (${this.formatCurrency(it.original_price)}) cho "${it.product_name}".`;
                    return;
                }
                if (it.sale_quantity <= 0) {
                    this.errorMessage = `Số lượng mở bán sale cho "${it.product_name}" phải lớn hơn 0.`;
                    return;
                }
            }

            this.isSubmitting = true;

            try {
                const payload = {
                    name: this.form.name,
                    description: this.form.description,
                    start_time: this.form.start_time,
                    end_time: this.form.end_time,
                    is_active: this.form.is_active ? 1 : 0,
                    items: this.items.map(it => ({
                        product_id: it.product_id,
                        product_variant_id: it.variant_id,
                        sale_price: it.sale_price,
                        sale_quantity: it.sale_quantity,
                    }))
                };

                const res = await fetch(`/admin/sale-campaigns/${this.form.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (!data.success) {
                    this.errorMessage = data.message || 'Lỗi cập nhật chiến dịch sale.';
                    return;
                }

                alert(data.message);
                window.location.href = data.redirect || "{{ route('admin.sale-campaigns.index') }}";
            } catch (e) {
                this.errorMessage = 'Lỗi gửi dữ liệu: ' + e.message;
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>
@endpush

