@extends('layouts.app')

@section('title', 'Giỏ hàng của bạn')
@section('meta_description', 'Xem lại giỏ hàng và tiến hành đặt hàng tại Hương Mít.')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="cartPage()">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-gray-500 mb-6">
        <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Trang chủ</a>
        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-800 font-semibold">Giỏ hàng của bạn</span>
    </nav>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-bold text-gray-800 flex items-center gap-3">
                <span>Giỏ hàng</span>
                <span class="text-sm font-sans font-semibold px-3 py-1 rounded-full bg-primary-100 text-primary"
                      x-text="items.length + ' sản phẩm'"></span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1">Kiểm tra lại danh sách sản phẩm và số lượng trước khi thanh toán</p>
        </div>

        <template x-if="items.length > 0">
            <button type="button" @click="clearAllCart()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-rose-500 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-all self-start sm:self-auto">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span>Xóa tất cả</span>
            </button>
        </template>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="py-20 text-center">
        <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
        <p class="text-sm text-gray-500 font-medium">Đang tải giỏ hàng...</p>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && items.length === 0" x-cloak class="bg-white rounded-3xl p-10 sm:p-16 text-center border border-primary-100 shadow-sm">
        <div class="w-24 h-24 rounded-full bg-primary-50 flex items-center justify-center mx-auto mb-6 text-primary">
            <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2 font-display">Giỏ hàng của bạn đang trống!</h3>
        <p class="text-sm text-gray-500 max-w-md mx-auto mb-8">
            Chưa có sản phẩm nào trong giỏ hàng. Hãy khám phá ngay các sản phẩm sữa, tã và đồ dùng cho bé với giá ưu đãi nhé!
        </p>
        <a href="{{ route('home') }}"
           class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl bg-gradient-to-r from-primary to-primary-600 text-white font-semibold text-sm shadow-md hover:shadow-lg hover:scale-105 transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Khám phá sản phẩm ngay</span>
        </a>
    </div>

    <!-- Cart Content Grid -->
    <div x-show="!loading && items.length > 0" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Cart Items List (8 cols) -->
        <div class="lg:col-span-8 space-y-4">
            
            <div class="bg-white rounded-3xl border border-primary-100/80 shadow-sm overflow-hidden">
                <!-- Mobile Select All Bar -->
                <div class="sm:hidden px-4 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-xs">
                    <label class="inline-flex items-center gap-2 cursor-pointer font-bold text-slate-700 select-none">
                        <input type="checkbox" 
                               :checked="isAllSelected" 
                               @change="toggleSelectAll($event.target.checked)"
                               class="w-4 h-4 rounded text-primary focus:ring-primary border-slate-300 cursor-pointer">
                        <span>Chọn tất cả (<span x-text="items.length"></span>)</span>
                    </label>
                    <span class="text-xs text-primary font-bold" x-show="selectedItemIds.length > 0" x-text="'Đã chọn ' + selectedItemIds.length + ' SP'"></span>
                </div>

                <!-- Desktop Table Header -->
                <div class="hidden sm:grid sm:grid-cols-12 px-6 py-3.5 bg-slate-50 border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-500 items-center">
                    <div class="sm:col-span-6 flex items-center gap-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" 
                                   :checked="isAllSelected" 
                                   @change="toggleSelectAll($event.target.checked)"
                                   class="w-4 h-4 rounded text-primary focus:ring-primary border-slate-300 cursor-pointer">
                            <span class="text-xs font-bold text-slate-700 normal-case">Chọn tất cả</span>
                        </label>
                        <span class="text-slate-300">|</span>
                        <span>Sản phẩm & Biến thể</span>
                    </div>
                    <div class="sm:col-span-2 text-right">Đơn giá</div>
                    <div class="sm:col-span-2 text-center">Số lượng</div>
                    <div class="sm:col-span-2 text-right">Tạm tính</div>
                </div>

                <!-- Items Loop -->
                <div class="divide-y divide-slate-100">
                    <template x-for="(item, idx) in items" :key="item.id">
                        <div class="p-4 sm:p-6 transition-colors hover:bg-slate-50/50"
                             :class="isItemSelected(item.id) ? 'bg-primary-50/20' : ''">
                            <div class="flex flex-col sm:grid sm:grid-cols-12 sm:items-center gap-4">
                                
                                <!-- Product & Variant Info -->
                                <div class="sm:col-span-6 flex items-center gap-3 sm:gap-3.5">
                                    <!-- Tickbox (Default Unchecked) -->
                                    <input type="checkbox" 
                                           :value="item.id" 
                                           :checked="isItemSelected(item.id)"
                                           @change="toggleItemSelect(item.id, $event.target.checked)"
                                           class="w-4 h-4 rounded text-primary focus:ring-primary border-slate-300 cursor-pointer flex-shrink-0">

                                    <!-- Image Thumbnail -->
                                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-milk border border-slate-200/80 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                        <template x-if="item.product && item.product.primary_image">
                                            <img :src="item.product.primary_image.image_url" :alt="item.product.name" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!item.product || !item.product.primary_image">
                                            <svg class="w-8 h-8 text-primary/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </template>
                                    </div>

                                    <!-- Details -->
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-800 text-xs sm:text-sm line-clamp-1 hover:text-primary transition-colors cursor-pointer"
                                            x-text="item.product ? item.product.name : 'Sản phẩm'"></h4>
                                        
                                        <!-- Variant Pill -->
                                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[11px] font-semibold bg-primary-50 text-primary border border-primary-200">
                                                <svg class="w-3 h-3 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                                </svg>
                                                <span x-text="item.variant ? item.variant.variant_title : 'Mặc định'"></span>
                                            </span>

                                            <!-- Sale Badge in Cart -->
                                            <template x-if="item.variant && parseFloat(item.price) < parseFloat(item.variant.price)">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                                    🔥 Giá Sale
                                                </span>
                                            </template>
                                        </div>

                                        <!-- Mobile Unit Price -->
                                        <div class="sm:hidden text-xs text-gray-500 mt-1">
                                            Đơn giá: <span class="font-bold text-gray-700" x-text="formatCurrency(item.price)"></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Unit Price (Desktop) -->
                                <div class="hidden sm:block sm:col-span-2 text-right">
                                    <span class="text-xs sm:text-sm font-semibold text-gray-700" x-text="formatCurrency(item.price)"></span>
                                </div>

                                <!-- Quantity Stepper -->
                                <div class="sm:col-span-2 flex items-center justify-between sm:justify-center">
                                    <div class="flex items-center border border-slate-200 rounded-xl bg-white shadow-sm overflow-hidden">
                                        <button type="button" 
                                                @click="changeQuantity(item, item.quantity - 1)"
                                                :disabled="item.quantity <= 1 || item.updating"
                                                class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-gray-500 hover:bg-primary-50 hover:text-primary disabled:opacity-30 disabled:cursor-not-allowed transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                        </button>
                                        
                                        <span class="w-8 sm:w-10 text-center font-bold text-xs sm:text-sm text-gray-800" x-text="item.quantity"></span>

                                        <button type="button" 
                                                @click="changeQuantity(item, item.quantity + 1)"
                                                :disabled="item.updating || (item.variant && item.quantity >= item.variant.stock_quantity)"
                                                class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center text-gray-500 hover:bg-primary-50 hover:text-primary disabled:opacity-30 disabled:cursor-not-allowed transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                    </div>

                                    <!-- Delete Button (Mobile) -->
                                    <button type="button" @click="removeItem(item)"
                                            class="sm:hidden p-2 text-gray-400 hover:text-rose-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>

                                <!-- Subtotal & Desktop Delete -->
                                <div class="sm:col-span-2 flex items-center justify-between sm:justify-end gap-3">
                                    <div class="sm:hidden text-xs text-gray-500">Thành tiền:</div>
                                    <div class="text-sm sm:text-base font-bold text-primary font-sans"
                                         x-text="formatCurrency(item.quantity * item.price)"></div>

                                    <!-- Delete Button (Desktop) -->
                                    <button type="button" @click="removeItem(item)"
                                            title="Xóa khỏi giỏ"
                                            class="hidden sm:inline-flex p-1.5 text-gray-300 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>

                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Back to shopping -->
            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('home') }}"
                   class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600 hover:text-primary transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Tiếp tục mua sắm thêm</span>
                </a>
            </div>
        </div>

        <!-- Order Summary Card (4 cols) -->
        <div class="lg:col-span-4 sticky top-24">
            <div class="bg-white rounded-3xl border border-primary-100 p-6 sm:p-7 shadow-sm space-y-6">
                
                <h3 class="text-base font-bold text-gray-800 font-display flex items-center justify-between">
                    <span>Tóm tắt đơn hàng</span>
                    <span class="w-2 h-2 rounded-full" :class="selectedItemIds.length > 0 ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                </h3>

                <!-- Price details (based on selected items) -->
                <div class="space-y-3 text-xs sm:text-sm">
                    <div class="flex justify-between text-gray-500">
                        <span>Số lượng đã chọn</span>
                        <span class="font-bold text-gray-800" x-text="totalQuantity + ' sản phẩm'"></span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>Tạm tính</span>
                        <span class="font-bold text-gray-800" x-text="formatCurrency(totalPrice)"></span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>Phí vận chuyển</span>
                        <span class="font-semibold text-slate-700">Thanh toán khi nhận hàng</span>
                    </div>
                    
                    <div class="border-t border-dashed border-slate-200 pt-3 flex justify-between items-baseline">
                        <span class="text-sm font-bold text-gray-800">Tổng thanh toán:</span>
                        <div class="text-right">
                            <span class="text-xl sm:text-2xl font-extrabold text-primary" x-text="formatCurrency(totalPrice)"></span>
                        </div>
                    </div>
                </div>

                <!-- Checkout Button -->
                <div class="space-y-2 pt-2">
                    <button type="button"
                            @click="proceedToCheckout()"
                            :disabled="selectedItemIds.length === 0"
                            :class="selectedItemIds.length === 0 ? 'opacity-40 cursor-not-allowed bg-slate-300' : 'bg-gradient-to-r from-primary to-primary-600 hover:shadow-xl hover:scale-[1.02] active:scale-[0.98] cursor-pointer'"
                            class="w-full py-3.5 rounded-2xl text-white font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="selectedItemIds.length > 0 ? ('Tiến hành đặt hàng (' + selectedItemIds.length + ')') : 'Chọn sản phẩm để thanh toán'"></span>
                    </button>
                </div>

            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
function cartPage() {
    return {
        items: @json($cart->items ?? []),
        selectedItemIds: [], // Mặc định để trống như yêu cầu
        loading: false,

        isItemSelected(id) {
            return this.selectedItemIds.some(i => Number(i) === Number(id));
        },

        toggleItemSelect(id, checked) {
            const numId = Number(id);
            if (checked) {
                if (!this.selectedItemIds.some(i => Number(i) === numId)) {
                    this.selectedItemIds.push(numId);
                }
            } else {
                this.selectedItemIds = this.selectedItemIds.filter(i => Number(i) !== numId);
            }
        },

        get isAllSelected() {
            return this.items.length > 0 && this.selectedItemIds.length === this.items.length;
        },

        toggleSelectAll(checked) {
            if (checked) {
                this.selectedItemIds = this.items.map(item => Number(item.id));
            } else {
                this.selectedItemIds = [];
            }
        },

        get selectedItems() {
            return this.items.filter(item => this.isItemSelected(item.id));
        },

        get totalQuantity() {
            return this.selectedItems.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
        },

        get totalPrice() {
            return this.selectedItems.reduce((sum, item) => sum + ((parseInt(item.quantity) || 0) * (parseFloat(item.price) || 0)), 0);
        },

        proceedToCheckout() {
            if (this.selectedItemIds.length === 0) {
                alert('Vui lòng tích chọn ít nhất 1 sản phẩm bạn muốn mua.');
                return;
            }
            window.location.href = `/thanh-toan?items=${this.selectedItemIds.join(',')}`;
        },

        formatCurrency(num) {
            return new Intl.NumberFormat('vi-VN').format(num || 0) + ' đ';
        },

        async changeQuantity(item, newQty) {
            if (newQty < 1) return;
            item.updating = true;

            try {
                const res = await fetch(`/gio-hang/${item.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ quantity: newQty })
                });

                const data = await res.json();
                if (!data.success) {
                    alert(data.message || 'Không thể cập nhật số lượng.');
                    return;
                }

                item.quantity = newQty;
                // Sync with global header cart
                window.dispatchEvent(new CustomEvent('cart-updated', {
                    detail: { count: data.count, total: data.total, items: data.items }
                }));
            } catch (e) {
                alert('Lỗi: ' + e.message);
            } finally {
                item.updating = false;
            }
        },

        async removeItem(item) {
            if (!confirm(`Bạn có chắc muốn xóa "${item.product ? item.product.name : 'sản phẩm'}" khỏi giỏ hàng?`)) return;

            try {
                const res = await fetch(`/gio-hang/${item.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                if (data.success) {
                    this.items = this.items.filter(i => i.id !== item.id);
                    this.selectedItemIds = this.selectedItemIds.filter(id => Number(id) !== Number(item.id));
                    window.dispatchEvent(new CustomEvent('cart-updated', {
                        detail: { count: data.count, total: data.total, items: data.items }
                    }));
                }
            } catch (e) {
                alert('Lỗi: ' + e.message);
            }
        },

        async clearAllCart() {
            if (!confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) return;

            try {
                const res = await fetch('/gio-hang/xoa-tat-ca', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                if (data.success) {
                    this.items = [];
                    this.selectedItemIds = [];
                    window.dispatchEvent(new CustomEvent('cart-updated', {
                        detail: { count: 0, total: 0, items: [] }
                    }));
                }
            } catch (e) {
                alert('Lỗi: ' + e.message);
            }
        }
    };
}
</script>
@endpush
