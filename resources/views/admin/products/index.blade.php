@extends('layouts.admin')

@section('title', 'Quản lý Sản phẩm & Biến thể')
@section('page_title', 'Danh sách Sản phẩm')

@section('content')
<div x-data="productManager()" x-init="init()" class="space-y-6">

    <!-- Top Action Bar: Search, Filters & Add Product Button -->
    <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

            <!-- Search & Filters Form -->
            <form method="GET" action="{{ route('admin.products.index') }}" class="flex-1 flex flex-wrap items-center gap-3">
                <!-- Search input (name or code SP0001) -->
                <div class="relative flex-1 min-w-[240px]">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Tìm theo tên hoặc mã SP (vd: SP0001, Bobby...)"
                           class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                </div>

                <!-- Category filter -->
                <div class="w-48">
                    <select name="category_id"
                            class="w-full px-3 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-700 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                        <option value="">Tất cả danh mục</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status filter -->
                <div class="w-36">
                    <select name="status"
                            class="w-full px-3 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-700 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Đang bán</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Đang ẩn</option>
                    </select>
                </div>

                <!-- Search button -->
                <button type="submit"
                        class="px-5 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Lọc
                </button>

                @if(request()->hasAny(['search', 'category_id', 'status']))
                    <a href="{{ route('admin.products.index') }}"
                       class="px-3 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium transition-all">
                        Đặt lại
                    </a>
                @endif
            </form>

            <!-- Add Product Button -->
            <button @click="openCreateModal()"
                    class="px-5 py-3 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-semibold text-xs tracking-wide shadow-lg shadow-primary/25 hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Thêm sản phẩm</span>
            </button>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-400 uppercase font-semibold">
                        <th class="py-4 px-4 w-12 text-center">#</th>
                        <th class="py-4 px-3 w-28">Mã SP</th>
                        <th class="py-4 px-3 w-20">Ảnh</th>
                        <th class="py-4 px-4">Tên sản phẩm</th>
                        <th class="py-4 px-4">Danh mục</th>
                        <th class="py-4 px-4 text-right">Giá gốc</th>
                        <th class="py-4 px-4 text-center">Tồn kho</th>
                        <th class="py-4 px-4 text-center">Ngày tạo</th>
                        <th class="py-4 px-4 text-center w-28">Trạng thái</th>
                        <th class="py-4 px-4 text-center w-20">COD</th>
                        <th class="py-4 px-4 text-right w-24">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $p)
                        <!-- Parent Product Row -->
                        <tr class="hover:bg-slate-50/80 transition-colors group cursor-pointer"
                            @click="toggleExpand({{ $p->id }})">

                            <!-- Expand icon -->
                            <td class="py-3 px-4 text-center">
                                <button type="button" class="w-6 h-6 rounded-lg bg-slate-100 group-hover:bg-primary/10 text-slate-400 group-hover:text-primary flex items-center justify-center transition-all"
                                        :class="{ 'rotate-90 bg-primary/10 text-primary': isExpanded({{ $p->id }}) }">
                                    <svg class="w-3.5 h-3.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </td>

                            <!-- Product Code (SP0001) -->
                            <td class="py-3 px-3 font-mono font-bold text-primary">
                                {{ $p->code }}
                            </td>

                            <!-- Product Primary Image -->
                            <td class="py-3 px-3">
                                <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200/80 overflow-hidden flex items-center justify-center">
                                    @if($p->primary_image_url)
                                        <img src="{{ $p->primary_image_url }}" alt="{{ $p->name }}" class="w-full h-full object-cover">
                                    @else
                                        <svg class="w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                </div>
                            </td>

                            <!-- Product Name & Variants count -->
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-800 text-xs sm:text-sm line-clamp-1 group-hover:text-primary transition-colors">
                                    {{ $p->name }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 font-medium text-slate-500">
                                        <svg class="w-3 h-3 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                        </svg>
                                        {{ $p->variants->count() }} biến thể
                                    </span>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                                    {{ $p->category->name ?? 'Chưa phân loại' }}
                                </span>
                            </td>

                            <!-- Base Price -->
                            <td class="py-3 px-4 text-right font-bold text-slate-800">
                                {{ number_format($p->base_price, 0, ',', '.') }} đ
                            </td>

                            <!-- Total Stock -->
                            <td class="py-3 px-4 text-center">
                                @if($p->total_stock > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ $p->total_stock }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        0
                                    </span>
                                @endif
                            </td>

                            <!-- Created Date -->
                            <td class="py-3 px-4 text-center text-slate-400 font-medium">
                                {{ $p->created_at ? $p->created_at->format('d/m/Y') : '—' }}
                            </td>

                            <!-- Toggle Status Switch -->
                            <td class="py-3 px-4 text-center" @click.stop>
                                <label class="relative inline-flex items-center cursor-pointer select-none" title="Trạng thái hiển thị">
                                    <input type="checkbox"
                                           {{ $p->status ? 'checked' : '' }}
                                           @change="toggleProductStatus({{ $p->id }}, $event.target)"
                                           class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                                </label>
                            </td>

                            <!-- Toggle COD Switch -->
                            <td class="py-3 px-4 text-center" @click.stop>
                                <label class="relative inline-flex items-center cursor-pointer select-none" title="Bật/Tắt COD cho sản phẩm này">
                                    <input type="checkbox"
                                           {{ ($p->allow_cod ?? true) ? 'checked' : '' }}
                                           @change="toggleProductCod({{ $p->id }}, $event.target)"
                                           class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                </label>
                            </td>

                            <!-- Edit & Actions -->
                            <td class="py-3 px-4 text-right" @click.stop>
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Sao chép link sản phẩm -->
                                    <button type="button"
                                            @click="copyProductLink({{ $p->id }}, '{{ addslashes($p->name) }}')"
                                            title="Sao chép link chia sẻ sản phẩm"
                                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-emerald-600 hover:text-white text-slate-600 flex items-center justify-center transition-all shadow-sm cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                        </svg>
                                    </button>

                                    <!-- Chỉnh sửa sản phẩm -->
                                    <button type="button"
                                            @click="openEditModal({{ $p->id }})"
                                            title="Chỉnh sửa sản phẩm"
                                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-primary hover:text-white text-slate-600 flex items-center justify-center transition-all shadow-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Child Variants Sub-table (Accordion) -->
                        <tr x-show="isExpanded({{ $p->id }})"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="bg-slate-50/70 border-b border-slate-100">
                            <td colspan="11" class="p-0">
                                <div class="py-4 px-6 sm:px-12 bg-slate-50/90 border-y border-slate-200/60"
                                     x-data="variantPaginator({{ json_encode($p->variants) }})">

                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-primary"></span>
                                            <h4 class="font-bold text-slate-800 text-xs uppercase tracking-wider">
                                                Danh sách biến thể con của [{{ $p->code }} - {{ $p->name }}]
                                            </h4>
                                            <span class="text-[11px] text-slate-400 font-medium">
                                                (Tổng cộng: <span x-text="variants.length"></span> biến thể)
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Variants Sub-table -->
                                    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                                        <table class="w-full text-left text-xs">
                                            <thead>
                                                <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-500 font-semibold uppercase text-[10px]">
                                                    <th class="py-2.5 px-4">#</th>
                                                    <th class="py-2.5 px-4">Thuộc tính / Tên biến thể</th>
                                                    <th class="py-2.5 px-4 text-right">Giá bán</th>
                                                    <th class="py-2.5 px-4 text-center">Số lượng tồn</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                                                <template x-for="(v, index) in paginatedVariants" :key="v.id">
                                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                                        <td class="py-2.5 px-4 text-slate-400 font-mono" x-text="((currentPage - 1) * perPage) + index + 1"></td>
                                                        <td class="py-2.5 px-4">
                                                             <div class="font-bold text-slate-800" x-text="v.variant_title || 'Mặc định'"></div>
                                                            <div class="text-[10px] text-slate-400 mt-0.5" x-show="v.attribute_values && v.attribute_values.length">
                                                                <template x-for="av in v.attribute_values" :key="av.id">
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 mr-1">
                                                                        <span x-text="av.attribute ? av.attribute.name + ': ' : ''"></span>
                                                                        <strong x-text="av.value"></strong>
                                                                    </span>
                                                                </template>
                                                            </div>
                                                        </td>
                                                        <td class="py-2.5 px-4 text-right font-bold text-primary" x-text="formatCurrency(v.price)"></td>
                                                        <td class="py-2.5 px-4 text-center">
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                                                  :class="v.stock_quantity > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                                                  x-text="v.stock_quantity">
                                                            </span>
                                                        </td>
                                                    </tr>
                                                </template>
                                                <tr x-show="variants.length === 0">
                                                    <td colspan="4" class="py-6 text-center text-slate-400">
                                                        Sản phẩm chưa có biến thể nào.
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Pagination indicator for sub-list (10 per page) -->
                                    <div x-show="totalPages > 1" class="flex items-center justify-end gap-2 text-xs mt-3">
                                        <span class="text-slate-400">
                                            Trang <span x-text="currentPage" class="font-bold text-slate-700"></span>/<span x-text="totalPages"></span>
                                        </span>
                                        <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 shadow-sm">
                                            <button type="button" @click="prevPage()" :disabled="currentPage === 1"
                                                    class="p-1 rounded text-slate-500 hover:text-slate-800 disabled:opacity-30 disabled:cursor-not-allowed">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                                                </svg>
                                            </button>
                                            <button type="button" @click="nextPage()" :disabled="currentPage === totalPages"
                                                    class="p-1 rounded text-slate-500 hover:text-slate-800 disabled:opacity-30 disabled:cursor-not-allowed">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-12 text-center text-slate-400">
                                <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-600">Không tìm thấy sản phẩm nào</p>
                                <p class="text-xs text-slate-400 mt-1">Hãy thử tìm kiếm với từ khóa khác hoặc nhấn "Thêm sản phẩm" mới.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($products->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/40">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: THÊM / CHỈNH SỬA SẢN PHẨM (POPUP FORM)                 -->
    <!-- Dùng x-teleport="body" để lớp phủ đen trùm 100% toàn màn hình  -->
    <!-- ============================================================== -->
    <template x-teleport="body">
        <div x-show="modalOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 w-screen h-screen z-[100] overflow-y-auto bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-3 sm:p-6"
             style="display: none;">

            <div class="bg-white w-full max-w-4xl rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden flex flex-col max-h-[92vh]">

                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50 to-white">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-rose-50 text-primary flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800" x-text="isEditMode ? 'Chỉnh sửa sản phẩm [' + form.code + ']' : 'Thêm sản phẩm mới'"></h3>
                            <p class="text-xs text-slate-400">Điền thông tin sản phẩm cha, thuộc tính và các biến thể sinh tự động</p>
                        </div>
                    </div>

                    <button @click="closeModal()" class="w-8 h-8 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-6 sm:p-8 space-y-6">

                    <!-- Alert Message -->
                    <div x-show="formError" x-text="formError" class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold"></div>

                    <!-- Section 1: Thông tin cơ bản -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                        <!-- Mã sản phẩm (Tự sinh & không được sửa) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Mã sản phẩm <span class="text-slate-400 font-normal">(tự sinh)</span>
                            </label>
                            <div class="relative">
                                <input type="text" :value="form.code" readonly disabled
                                       class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 bg-slate-100 text-primary font-mono font-bold text-xs cursor-not-allowed">
                                <span class="absolute right-3 top-2.5 text-[10px] text-slate-400 font-medium">Khóa</span>
                            </div>
                        </div>

                        <!-- Tên sản phẩm -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Tên sản phẩm <span class="text-primary">*</span>
                            </label>
                            <input type="text" x-model="form.name" placeholder="Ví dụ: Sữa bột Friso Gold Pro số 3 800g"
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                        </div>
                    </div>

                    <!-- Section 2: Danh mục & Giá chung -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-start">

                        <!-- Danh mục (Dropdown có nút sửa bên cạnh & tùy chọn cuối là thêm danh mục) -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Danh mục <span class="text-primary">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <select x-model="form.category_id" @change="handleCategorySelect($event)"
                                        class="flex-1 px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-800 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                                    <option value="">-- Chọn danh mục sản phẩm --</option>
                                    <template x-for="cat in categoryList" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.name"></option>
                                    </template>
                                    <option value="__NEW_CATEGORY__" class="font-bold text-primary bg-rose-50">
                                        + Thêm danh mục mới...
                                    </option>
                                </select>

                                <!-- Nút sửa danh mục đã chọn -->
                                <button type="button" @click="openEditCategoryModal()"
                                        :disabled="!form.category_id || form.category_id === '__NEW_CATEGORY__'"
                                        title="Chỉnh sửa danh mục đang chọn"
                                        class="p-2.5 rounded-2xl border border-slate-200 hover:border-primary hover:bg-primary/10 text-slate-600 hover:text-primary disabled:opacity-30 disabled:pointer-events-none transition-all">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>

                                <!-- Nút thêm nhanh danh mục -->
                                <button type="button" @click="openAddCategoryModal()"
                                        title="Thêm danh mục mới"
                                        class="p-2.5 rounded-2xl bg-slate-900 text-white hover:bg-slate-800 transition-all">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Giá chung ban đầu (Tự format kiểu 230.000) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Giá chung ban đầu (VNĐ) <span class="text-primary">*</span>
                            </label>
                            <input type="text"
                                   :value="formatCurrencyInput(form.base_price)"
                                   @input="onBasePriceInput($event)"
                                   placeholder="230.000"
                                   class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-800 font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                            <span class="text-[10px] text-slate-400 mt-1 block">Tự động áp dụng cho các biến thể</span>
                        </div>
                    </div>

                    <!-- Section 3: Ảnh sản phẩm (Tối đa 5 ảnh, mỗi ảnh < 5MB) -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-slate-700">
                                Ảnh sản phẩm <span class="text-slate-400 font-normal">(Tối đa 5 ảnh, dung lượng dưới 5MB/ảnh)</span>
                            </label>
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                                  :class="(existingImages.length + newImages.length) >= 5 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'">
                                <span x-text="existingImages.length + newImages.length"></span>/5 ảnh
                            </span>
                        </div>

                        <!-- Image Previews Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                            <!-- Existing Images (Edit mode) -->
                            <template x-for="(img, idx) in existingImages" :key="'exist_' + img.id">
                                <div class="relative group aspect-square rounded-2xl bg-slate-100 border-2 overflow-hidden shadow-xs transition-all"
                                     :class="img.is_primary ? 'border-primary ring-2 ring-primary/20' : 'border-slate-200 hover:border-slate-300'">
                                    <img :src="img.image_url" class="w-full h-full object-cover">

                                    <!-- Primary Badge or Set Primary Button -->
                                    <div class="absolute top-1.5 left-1.5 z-10">
                                        <template x-if="img.is_primary">
                                            <span class="px-1.5 py-0.5 rounded-md bg-primary text-white text-[9px] font-bold shadow-xs">
                                                Ảnh chính
                                            </span>
                                        </template>
                                        <template x-if="!img.is_primary">
                                            <button type="button" @click="setPrimary('existing', img.id)"
                                                    title="Đặt làm ảnh chính"
                                                    class="opacity-0 group-hover:opacity-100 px-1.5 py-0.5 rounded-md bg-slate-900/80 hover:bg-primary text-white text-[9px] font-bold shadow-xs transition-opacity">
                                                Làm ảnh chính
                                            </button>
                                        </template>
                                    </div>

                                    <!-- Delete Button -->
                                    <button type="button" @click="removeExistingImage(img.id)"
                                            title="Xóa ảnh này"
                                            class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-rose-500 hover:bg-rose-600 text-white flex items-center justify-center shadow-xs transition-transform hover:scale-110">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>

                            <!-- Newly Added Images -->
                            <template x-for="(img, idx) in newImages" :key="'new_' + idx">
                                <div class="relative group aspect-square rounded-2xl bg-slate-100 border-2 overflow-hidden shadow-xs transition-all"
                                     :class="img.is_primary ? 'border-primary ring-2 ring-primary/20' : 'border-slate-200 hover:border-slate-300'">
                                    <img :src="img.previewUrl" class="w-full h-full object-cover">

                                    <!-- Primary Badge or Set Primary Button -->
                                    <div class="absolute top-1.5 left-1.5 z-10">
                                        <template x-if="img.is_primary">
                                            <span class="px-1.5 py-0.5 rounded-md bg-primary text-white text-[9px] font-bold shadow-xs">
                                                Ảnh chính
                                            </span>
                                        </template>
                                        <template x-if="!img.is_primary">
                                            <button type="button" @click="setPrimary('new', idx)"
                                                    title="Đặt làm ảnh chính"
                                                    class="opacity-0 group-hover:opacity-100 px-1.5 py-0.5 rounded-md bg-slate-900/80 hover:bg-primary text-white text-[9px] font-bold shadow-xs transition-opacity">
                                                Làm ảnh chính
                                            </button>
                                        </template>
                                    </div>

                                    <!-- Delete Button -->
                                    <button type="button" @click="removeNewImage(idx)"
                                            title="Xóa ảnh này"
                                            class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-rose-500 hover:bg-rose-600 text-white flex items-center justify-center shadow-xs transition-transform hover:scale-110">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>

                            <!-- Add More Button (hidden if total >= 5) -->
                            <template x-if="(existingImages.length + newImages.length) < 5">
                                <label class="aspect-square rounded-2xl border-2 border-dashed border-slate-300 hover:border-primary bg-slate-50/50 hover:bg-primary-50/30 flex flex-col items-center justify-center cursor-pointer transition-all p-2 text-center group">
                                    <input type="file" id="product_images_input" multiple accept="image/*" @change="handleMultiImageChange($event)" class="hidden">
                                    <div class="w-8 h-8 rounded-full bg-white shadow-xs group-hover:bg-primary group-hover:text-white text-slate-400 flex items-center justify-center transition-all mb-1">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-600 group-hover:text-primary">Thêm ảnh</span>
                                    <span class="text-[9px] text-slate-400">(Tối đa 5 ảnh)</span>
                                </label>
                            </template>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-2">Định dạng hỗ trợ: JPG, PNG, WEBP (tối đa 5120KB mỗi ảnh, tối đa 5 ảnh cho mỗi sản phẩm).</p>
                    </div>

                    <!-- Section 4: Mô tả sản phẩm -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Mô tả sản phẩm</label>
                        <textarea x-model="form.description" rows="2" placeholder="Mô tả thông tin dinh dưỡng, xuất xứ, độ tuổi sử dụng..."
                                  class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all"></textarea>
                    </div>

                    <!-- Section 5: Thuộc tính sản phẩm (Không được trùng nhau, nhập giá trị rồi enter) -->
                    <!-- Ở form Chỉnh sửa: KHÔNG CHO PHÉP SỬA THUỘC TÍNH (Chỉ xem và cập nhật giá/tồn kho biến thể) -->
                    <div class="pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Thuộc tính sản phẩm</h4>
                                <p class="text-[11px] text-slate-400">Chọn thuộc tính và gõ giá trị rồi bấm Enter (vd: Vani, Dâu, 400g, 800g...)</p>
                            </div>

                            <!-- Nút thêm thuộc tính: CHỈ HIỆN KHI THÊM MỚI -->
                            <button type="button" x-show="!isEditMode" @click="addAttributeRow()"
                                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-primary/10 text-slate-700 hover:text-primary text-xs font-bold transition-all flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span>Thêm thuộc tính</span>
                            </button>
                        </div>

                        <!-- Thông báo khi ở chế độ chỉnh sửa -->
                        <div x-show="isEditMode" class="p-3 mb-3 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-800 text-xs flex items-center gap-2">
                            <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Thuộc tính sản phẩm được cố định khi chỉnh sửa để bảo toàn cấu trúc dữ liệu. Bạn có thể cập nhật giá bán và tồn kho cho từng biến thể bên dưới.</span>
                        </div>

                        <!-- Attribute Rows List -->
                        <div class="space-y-3">
                            <template x-for="(row, rIndex) in selectedAttributes" :key="row.uid">
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <!-- Dropdown Attribute: Mặc định là "-- Chọn thuộc tính --" -->
                                        <div class="w-56">
                                            <select x-model="row.attribute_id"
                                                    :disabled="isEditMode"
                                                    @change="handleAttributeSelect(rIndex, $event)"
                                                    class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs font-medium text-slate-800 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none disabled:bg-slate-100 disabled:text-slate-500 disabled:cursor-not-allowed">
                                                <option value="">-- Chọn thuộc tính --</option>
                                                <template x-for="attr in getAvailableAttributes(row.attribute_id)" :key="attr.id">
                                                    <option :value="attr.id" x-text="attr.name"></option>
                                                </template>
                                                <option x-show="!isEditMode" value="__NEW_ATTRIBUTE__" class="font-bold text-primary bg-rose-50">
                                                    + Thêm thuộc tính mới...
                                                </option>
                                            </select>
                                        </div>

                                        <!-- Edit attribute button (Ẩn khi edit) -->
                                        <button type="button" x-show="!isEditMode" @click="openEditAttributeModal(row.attribute_id)"
                                                :disabled="!row.attribute_id || row.attribute_id === '__NEW_ATTRIBUTE__'"
                                                title="Sửa tên thuộc tính"
                                                class="p-2 rounded-xl border border-slate-200 hover:border-primary hover:bg-primary/10 text-slate-600 hover:text-primary disabled:opacity-30 disabled:pointer-events-none transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>

                                        <!-- Value tag input: Type and press Enter (CHỈ HIỆN KHI THÊM MỚI) -->
                                        <div class="flex-1 relative" x-show="!isEditMode">
                                            <input type="text" x-model="row.inputValue"
                                                   @keydown.enter.prevent="addTagValue(rIndex)"
                                                   placeholder="Nhập giá trị rồi nhấn Enter... (vd: Vani, 800g)"
                                                   :disabled="!row.attribute_id"
                                                   class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none disabled:bg-slate-100 disabled:cursor-not-allowed">
                                        </div>

                                        <!-- Delete Attribute Row button (Ẩn khi edit) -->
                                        <button type="button" x-show="!isEditMode" @click="removeAttributeRow(rIndex)"
                                                title="Xóa thuộc tính này"
                                                class="p-2 rounded-xl text-rose-500 hover:bg-rose-50 transition-all">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Tags list for this attribute -->
                                    <div class="flex flex-wrap gap-1.5 pt-1" x-show="row.values.length > 0">
                                        <template x-for="(val, vIndex) in row.values" :key="val.id || vIndex">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-white border border-slate-200 text-slate-700 shadow-sm">
                                                <span x-text="val.value"></span>
                                                <!-- Nút xóa tag: Ẩn khi edit -->
                                                <button type="button" x-show="!isEditMode" @click="removeTagValue(rIndex, vIndex)" class="text-slate-400 hover:text-rose-500">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <div x-show="selectedAttributes.length === 0" class="text-center py-4 text-xs text-slate-400 border border-dashed border-slate-200 rounded-2xl">
                                Chưa chọn thuộc tính nào. Sản phẩm sẽ được tạo với 1 biến thể mặc định (dùng giá chung).
                            </div>
                        </div>
                    </div>

                    <!-- Section 6: Danh sách biến thể tự sinh (Matrix Variants) -->
                    <div class="pt-2 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                                    Danh sách các biến thể (<span x-text="generatedVariants.length"></span>)
                                </h4>
                                <p class="text-[11px] text-slate-400">Dùng giá chung ban đầu, có thể tùy chỉnh giá bán và tồn kho riêng cho từng biến thể</p>
                            </div>
                        </div>

                        <!-- Variants Table -->
                        <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-sm max-h-60 overflow-y-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="sticky top-0 bg-slate-100 border-b border-slate-200 z-10 text-[11px] font-semibold text-slate-600">
                                    <tr>
                                        <th class="py-2.5 px-3">Tên biến thể</th>
                                        <th class="py-2.5 px-3 w-48">Giá bán (VNĐ)</th>
                                        <th class="py-2.5 px-3 w-32 text-center">Tồn kho</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(gv, gIdx) in generatedVariants" :key="gv.key || gIdx">
                                        <tr class="hover:bg-slate-50">
                                            <td class="py-2 px-3 font-semibold text-slate-800">
                                                <span x-text="gv.title"></span>
                                            </td>
                                            <!-- Giá bán tự format 230.000 -->
                                            <td class="py-2 px-3">
                                                <input type="text"
                                                       :value="formatCurrencyInput(gv.price)"
                                                       @input="onVariantPriceInput(gv, $event)"
                                                       placeholder="230.000"
                                                       class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-800 font-bold focus:ring-1 focus:ring-primary focus:border-primary outline-none">
                                            </td>
                                            <!-- Tồn kho -->
                                            <td class="py-2 px-3">
                                                <input type="number" x-model="gv.stock_quantity" min="0" placeholder="0"
                                                       class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-800 text-center font-bold focus:ring-1 focus:ring-primary focus:border-primary outline-none">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Modal Footer: Hủy, Lưu, Lưu và thêm mới -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/70 flex flex-wrap items-center justify-between gap-3">
                    <button type="button" @click="closeModal()"
                            class="px-5 py-2.5 rounded-2xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold transition-all">
                        Hủy bỏ
                    </button>

                    <div class="flex items-center gap-2">
                        <!-- Nút Lưu và thêm mới (chỉ hiện khi Thêm mới) -->
                        <button type="button" x-show="!isEditMode" @click="submitProductForm(true)" :disabled="isSubmitting"
                                class="px-5 py-2.5 rounded-2xl border border-primary text-primary hover:bg-primary-50 font-semibold text-xs transition-all disabled:opacity-50">
                            <span x-show="!isSubmitting">Lưu và thêm mới</span>
                            <span x-show="isSubmitting">Đang lưu...</span>
                        </button>

                        <!-- Nút Lưu -->
                        <button type="button" @click="submitProductForm(false)" :disabled="isSubmitting"
                                class="px-6 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white font-semibold text-xs shadow-md shadow-primary/20 transition-all disabled:opacity-50">
                            <span x-show="!isSubmitting">Lưu sản phẩm</span>
                            <span x-show="isSubmitting">Đang lưu...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- ============================================================== -->
    <!-- MODAL: THÊM / SỬA DANH MỤC (POPUP CON)                         -->
    <!-- Thoát không cần thông báo, quay lại luôn form thêm sản phẩm    -->
    <!-- ============================================================== -->
    <template x-teleport="body">
        <div x-show="categoryModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="fixed inset-0 w-screen h-screen z-[110] overflow-y-auto bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4"
             style="display: none;">
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-sm" x-text="categoryForm.id ? 'Sửa danh mục' : 'Thêm danh mục mới'"></h3>
                    <button @click="categoryModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tên danh mục <span class="text-primary">*</span></label>
                        <input type="text" x-model="categoryForm.name" placeholder="Ví dụ: Tã bỉm cho bé"
                               class="w-full px-3.5 py-2 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mô tả</label>
                        <textarea x-model="categoryForm.description" rows="2" placeholder="Mô tả danh mục..."
                                  class="w-full px-3.5 py-2 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="categoryModalOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Đóng
                    </button>
                    <button type="button" @click="saveCategory()" :disabled="categorySaving"
                            class="px-5 py-2 rounded-xl bg-primary text-white text-xs font-semibold hover:bg-primary-600 transition-all disabled:opacity-50">
                        <span x-show="!categorySaving">Lưu danh mục</span>
                        <span x-show="categorySaving">Đang lưu...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- ============================================================== -->
    <!-- MODAL: THÊM / SỬA THUỘC TÍNH (POPUP CON)                       -->
    <!-- Thoát không cần thông báo, quay lại luôn form thêm sản phẩm    -->
    <!-- ============================================================== -->
    <template x-teleport="body">
        <div x-show="attributeModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="fixed inset-0 w-screen h-screen z-[110] overflow-y-auto bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4"
             style="display: none;">
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 text-sm" x-text="attributeForm.id ? 'Sửa thuộc tính' : 'Thêm thuộc tính mới'"></h3>
                    <button @click="attributeModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tên thuộc tính <span class="text-primary">*</span></label>
                        <input type="text" x-model="attributeForm.name" placeholder="Ví dụ: Hương vị, Trọng lượng, Kích cỡ..."
                               class="w-full px-3.5 py-2 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mã thuộc tính <span class="text-slate-400 font-normal">(tùy chọn)</span></label>
                        <input type="text" x-model="attributeForm.code" placeholder="huong_vi, size_ta..."
                               class="w-full px-3.5 py-2 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="attributeModalOpen = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Đóng
                    </button>
                    <button type="button" @click="saveAttribute()" :disabled="attributeSaving"
                            class="px-5 py-2 rounded-xl bg-primary text-white text-xs font-semibold hover:bg-primary-600 transition-all disabled:opacity-50">
                        <span x-show="!attributeSaving">Lưu thuộc tính</span>
                        <span x-show="attributeSaving">Đang lưu...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
    /**
     * Sub-table Paginator component: paginates child variants list (10 per page)
     */
    function variantPaginator(initialVariants) {
        return {
            variants: initialVariants || [],
            perPage: 10,
            currentPage: 1,

            get totalPages() {
                return Math.max(1, Math.ceil(this.variants.length / this.perPage));
            },

            get paginatedVariants() {
                const start = (this.currentPage - 1) * this.perPage;
                return this.variants.slice(start, start + this.perPage);
            },

            prevPage() {
                if (this.currentPage > 1) this.currentPage--;
            },

            nextPage() {
                if (this.currentPage < this.totalPages) this.currentPage++;
            },

            formatCurrency(amount) {
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount || 0);
            }
        };
    }

    /**
     * Main Product Manager Alpine Component
     */
    function productManager() {
        return {
            // State
            expandedRows: [],
            modalOpen: false,
            isEditMode: false,
            isSubmitting: false,
            formError: '',
            existingImages: [],
            newImages: [],
            deletedImageIds: [],

            // Server-provided data
            nextCode: '{{ $nextCode }}',
            categoryList: @json($categories),
            attributeList: @json($attributes),

            // Form data
            form: {
                id: null,
                code: '{{ $nextCode }}',
                name: '',
                category_id: '',
                base_price: '',
                description: '',
            },

            // Selected attributes in the modal: [{ uid, attribute_id, inputValue, values: [{id, value}] }]
            selectedAttributes: [],

            // Generated variants matrix
            generatedVariants: [],

            // Category Modal state
            categoryModalOpen: false,
            categorySaving: false,
            categoryForm: { id: null, name: '', description: '' },

            // Attribute Modal state
            attributeModalOpen: false,
            attributeSaving: false,
            targetRowIndex: null,
            attributeForm: { id: null, name: '', code: '' },

            init() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('action') === 'create') {
                    this.openCreateModal();
                }
            },

            // Currency formatting helper: converts number/string to "230.000"
            formatCurrencyInput(val) {
                if (val === null || val === undefined || val === '') return '';
                if (val === 0 || val === '0') return '0';
                let clean = (val + '').replace(/\D/g, '');
                if (!clean) return '';
                return new Intl.NumberFormat('vi-VN').format(parseInt(clean, 10));
            },

            // Currency parser helper: converts "230.000" to number 230000
            parseCurrencyInput(val) {
                if (val === null || val === undefined || val === '') return 0;
                let clean = (val + '').replace(/\D/g, '');
                return clean ? parseInt(clean, 10) : 0;
            },

            // Event handler for base price input (realtime formatting)
            onBasePriceInput(event) {
                const raw = (event.target.value || '').trim();
                if (raw === '') {
                    this.form.base_price = '';
                    event.target.value = '';
                    this.syncBasePriceToVariants();
                    return;
                }
                let num = this.parseCurrencyInput(raw);
                this.form.base_price = num;
                event.target.value = this.formatCurrencyInput(num);
                this.syncBasePriceToVariants();
            },

            // Event handler for individual variant price input
            onVariantPriceInput(gv, event) {
                const raw = (event.target.value || '').trim();
                gv.isCustomPrice = true;
                if (raw === '') {
                    gv.price = '';
                    event.target.value = '';
                } else {
                    gv.price = this.parseCurrencyInput(raw);
                    event.target.value = this.formatCurrencyInput(gv.price);
                }
            },

            // Accordion expand/collapse (Chỉ mở 1 sản phẩm tại 1 thời điểm, mở cái mới sẽ tự thu cái cũ lại)
            toggleExpand(productId) {
                if (this.expandedRows.includes(productId)) {
                    this.expandedRows = [];
                } else {
                    this.expandedRows = [productId];
                }
            },

            isExpanded(productId) {
                return this.expandedRows.includes(productId);
            },

            // Open Create Modal
            openCreateModal() {
                this.isEditMode = false;
                this.formError = '';
                this.existingImages = [];
                this.newImages = [];
                this.deletedImageIds = [];
                const fileInput = document.getElementById('product_images_input');
                if (fileInput) fileInput.value = '';

                this.form = {
                    id: null,
                    code: this.nextCode,
                    name: '',
                    category_id: this.categoryList.length ? this.categoryList[0].id : '',
                    base_price: '',
                    description: '',
                };

                this.selectedAttributes = [];
                this.generatedVariants = [];
                this.generateVariantsMatrix();
                this.modalOpen = true;
            },

            // Open Edit Modal (Khóa sửa thuộc tính)
            async openEditModal(productId) {
                this.isEditMode = true;
                this.formError = '';
                this.existingImages = [];
                this.newImages = [];
                this.deletedImageIds = [];
                const fileInput = document.getElementById('product_images_input');
                if (fileInput) fileInput.value = '';

                try {
                    const res = await fetch(`/admin/products/${productId}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();

                    if (!data.success || !data.product) {
                        alert('Không tìm thấy thông tin sản phẩm.');
                        return;
                    }

                    const p = data.product;
                    this.form = {
                        id: p.id,
                        code: p.code,
                        name: p.name,
                        category_id: p.category_id,
                        base_price: parseFloat(p.base_price) || 0,
                        description: p.description || '',
                    };

                    this.existingImages = (p.images || []).map(img => ({
                        id: img.id,
                        image_url: img.image_url,
                        is_primary: Boolean(img.is_primary)
                    }));

                    // Extract attributes and values for read-only display
                    this.selectedAttributes = [];
                    const attrMap = {};

                    if (p.variants && p.variants.length) {
                        p.variants.forEach(variant => {
                            if (variant.attribute_values && variant.attribute_values.length) {
                                variant.attribute_values.forEach(av => {
                                    if (!attrMap[av.attribute_id]) {
                                        attrMap[av.attribute_id] = {
                                            uid: Date.now() + Math.random(),
                                            attribute_id: av.attribute_id,
                                            inputValue: '',
                                            values: []
                                        };
                                    }
                                    if (!attrMap[av.attribute_id].values.some(v => v.id === av.id)) {
                                        attrMap[av.attribute_id].values.push({
                                            id: av.id,
                                            value: av.value
                                        });
                                    }
                                });
                            }
                        });
                    }

                    this.selectedAttributes = Object.values(attrMap);

                    // Load existing variants directly to edit prices & stock
                    this.generatedVariants = p.variants.map(v => {
                        return {
                            id: v.id,
                            key: 'v_' + v.id,
                            title: v.variant_title || 'Mặc định',
                            isCustomPrice: true,
                            price: parseInt(v.price, 10) || 0,
                            stock_quantity: v.stock_quantity || 0,
                            attribute_value_ids: (v.attribute_values || []).map(av => av.id)
                        };
                    });

                    this.modalOpen = true;
                } catch (e) {
                    alert('Lỗi tải sản phẩm: ' + e.message);
                }
            },

            closeModal() {
                this.modalOpen = false;
            },

            // Handle Multi-Image Upload (Max 5 images per product, < 5MB each)
            handleMultiImageChange(event) {
                const files = Array.from(event.target.files || []);
                if (!files.length) return;

                const currentCount = this.existingImages.length + this.newImages.length;
                const available = 5 - currentCount;

                if (available <= 0) {
                    alert('Mỗi sản phẩm chỉ được có tối đa 5 ảnh. Vui lòng xóa bớt ảnh trước khi thêm.');
                    event.target.value = '';
                    return;
                }

                if (files.length > available) {
                    alert('Mỗi sản phẩm chỉ được có tối đa 5 ảnh. Bạn đang chọn ' + files.length + ' ảnh nhưng chỉ còn trống ' + available + ' vị trí. Hệ thống sẽ lấy ' + available + ' ảnh đầu tiên.');
                }

                const toProcess = files.slice(0, available);

                toProcess.forEach(file => {
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Ảnh [' + file.name + '] vượt quá 5MB nên bị bỏ qua.');
                        return;
                    }

                    const isFirstEver = (this.existingImages.length === 0 && this.newImages.length === 0);
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.newImages.push({
                            file: file,
                            previewUrl: e.target.result,
                            is_primary: isFirstEver
                        });
                    };
                    reader.readAsDataURL(file);
                });

                event.target.value = '';
            },

            removeExistingImage(id) {
                const idx = this.existingImages.findIndex(img => img.id === id);
                if (idx === -1) return;

                const wasPrimary = this.existingImages[idx].is_primary;
                this.deletedImageIds.push(id);
                this.existingImages.splice(idx, 1);

                if (wasPrimary) {
                    if (this.existingImages.length > 0) {
                        this.existingImages[0].is_primary = true;
                    } else if (this.newImages.length > 0) {
                        this.newImages[0].is_primary = true;
                    }
                }
            },

            removeNewImage(idx) {
                if (idx < 0 || idx >= this.newImages.length) return;
                const wasPrimary = this.newImages[idx].is_primary;
                this.newImages.splice(idx, 1);

                if (wasPrimary) {
                    if (this.existingImages.length > 0) {
                        this.existingImages[0].is_primary = true;
                    } else if (this.newImages.length > 0) {
                        this.newImages[0].is_primary = true;
                    }
                }
            },

            setPrimary(type, identifier) {
                this.existingImages.forEach(img => img.is_primary = false);
                this.newImages.forEach(img => img.is_primary = false);

                if (type === 'existing') {
                    const found = this.existingImages.find(img => img.id === identifier);
                    if (found) found.is_primary = true;
                } else if (type === 'new') {
                    if (this.newImages[identifier]) {
                        this.newImages[identifier].is_primary = true;
                    }
                }
            },

            // Category select handler: detect "+ Thêm danh mục mới..."
            handleCategorySelect(event) {
                if (this.form.category_id === '__NEW_CATEGORY__') {
                    this.form.category_id = '';
                    this.openAddCategoryModal();
                }
            },

            openAddCategoryModal() {
                this.categoryForm = { id: null, name: '', description: '' };
                this.categoryModalOpen = true;
            },

            openEditCategoryModal() {
                const cat = this.categoryList.find(c => c.id == this.form.category_id);
                if (!cat) return;
                this.categoryForm = { id: cat.id, name: cat.name, description: cat.description || '' };
                this.categoryModalOpen = true;
            },

            async saveCategory() {
                if (!this.categoryForm.name.trim()) {
                    alert('Vui lòng nhập tên danh mục.');
                    return;
                }
                this.categorySaving = true;

                try {
                    const url = this.categoryForm.id
                        ? `/admin/categories/${this.categoryForm.id}`
                        : '/admin/categories';
                    const method = this.categoryForm.id ? 'PUT' : 'POST';

                    const res = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.categoryForm)
                    });

                    const data = await res.json();
                    if (!data.success) {
                        alert(data.message || 'Lỗi lưu danh mục.');
                        return;
                    }

                    if (this.categoryForm.id) {
                        const idx = this.categoryList.findIndex(c => c.id === this.categoryForm.id);
                        if (idx > -1) this.categoryList[idx] = data.category;
                    } else {
                        this.categoryList.push(data.category);
                        this.form.category_id = data.category.id;
                    }

                    this.categoryModalOpen = false;
                } catch (e) {
                    alert('Lỗi: ' + e.message);
                } finally {
                    this.categorySaving = false;
                }
            },

            // Attribute Selection & Rows Management
            getAvailableAttributes(currentAttributeId) {
                const selectedIds = this.selectedAttributes
                    .map(r => parseInt(r.attribute_id))
                    .filter(id => id && id !== parseInt(currentAttributeId));

                return this.attributeList.filter(a => !selectedIds.includes(a.id));
            },

            // Bấm thêm thuộc tính: hiển thị placeholder chọn thuộc tính, KHÔNG tự gán hương vị
            addAttributeRow() {
                this.selectedAttributes.push({
                    uid: Date.now() + Math.random(),
                    attribute_id: '', // Mặc định để trống để hiện placeholder
                    inputValue: '',
                    values: []
                });
            },

            removeAttributeRow(index) {
                this.selectedAttributes.splice(index, 1);
                this.generateVariantsMatrix();
            },

            handleAttributeSelect(index, event) {
                const row = this.selectedAttributes[index];
                if (row.attribute_id === '__NEW_ATTRIBUTE__') {
                    row.attribute_id = '';
                    this.targetRowIndex = index;
                    this.openAddAttributeModal();
                    return;
                }

                // Check duplicate attribute
                const count = this.selectedAttributes.filter(r => r.attribute_id && r.attribute_id == row.attribute_id).length;
                if (count > 1) {
                    alert('Thuộc tính này đã được chọn. Mỗi sản phẩm không được chọn trùng thuộc tính.');
                    row.attribute_id = '';
                    return;
                }

                row.values = [];
                this.generateVariantsMatrix();
            },

            openAddAttributeModal() {
                this.attributeForm = { id: null, name: '', code: '' };
                this.attributeModalOpen = true;
            },

            openEditAttributeModal(attributeId) {
                const attr = this.attributeList.find(a => a.id == attributeId);
                if (!attr) return;
                this.attributeForm = { id: attr.id, name: attr.name, code: attr.code || '' };
                this.attributeModalOpen = true;
            },

            async saveAttribute() {
                if (!this.attributeForm.name.trim()) {
                    alert('Vui lòng nhập tên thuộc tính.');
                    return;
                }
                this.attributeSaving = true;

                try {
                    const url = this.attributeForm.id
                        ? `/admin/attributes/${this.attributeForm.id}`
                        : '/admin/attributes';
                    const method = this.attributeForm.id ? 'PUT' : 'POST';

                    const res = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.attributeForm)
                    });

                    const data = await res.json();
                    if (!data.success) {
                        alert(data.message || 'Lỗi lưu thuộc tính.');
                        return;
                    }

                    if (this.attributeForm.id) {
                        const idx = this.attributeList.findIndex(a => a.id === this.attributeForm.id);
                        if (idx > -1) this.attributeList[idx] = data.attribute;
                    } else {
                        this.attributeList.push(data.attribute);
                        if (this.targetRowIndex !== null && this.selectedAttributes[this.targetRowIndex]) {
                            this.selectedAttributes[this.targetRowIndex].attribute_id = data.attribute.id;
                        }
                    }

                    this.attributeModalOpen = false;
                } catch (e) {
                    alert('Lỗi: ' + e.message);
                } finally {
                    this.attributeSaving = false;
                    this.targetRowIndex = null;
                }
            },

            // Tag Value input: Bấm Enter CHỈ lưu tạm trên giao diện, KHÔNG tự gọi API vào DB ngay
            addTagValue(rowIndex) {
                const row = this.selectedAttributes[rowIndex];
                const text = (row.inputValue || '').trim();
                if (!text) return;

                if (!row.attribute_id) {
                    alert('Vui lòng chọn thuộc tính trước khi nhập giá trị.');
                    return;
                }

                // Check duplicate in same attribute
                if (row.values.some(v => v.value.toLowerCase() === text.toLowerCase())) {
                    alert('Giá trị "' + text + '" đã có trong thuộc tính này.');
                    row.inputValue = '';
                    return;
                }

                // Push vào danh sách tạm thời trên frontend
                row.values.push({
                    id: null,
                    value: text
                });
                row.inputValue = '';
                this.generateVariantsMatrix();
            },

            removeTagValue(rowIndex, valIndex) {
                this.selectedAttributes[rowIndex].values.splice(valIndex, 1);
                this.generateVariantsMatrix();
            },

            // Auto-generate variants from Cartesian Product of all attributes' values
            generateVariantsMatrix() {
                // If in Edit mode, don't regenerate matrix from scratch
                if (this.isEditMode) return;

                const base = this.parseCurrencyInput(this.form.base_price);
                const validRows = this.selectedAttributes.filter(r => r.attribute_id && r.values.length > 0);

                if (validRows.length === 0) {
                    // Single default variant using base price
                    this.generatedVariants = [{
                        key: 'default',
                        title: 'Mặc định (Không có thuộc tính)',
                        isCustomPrice: false,
                        price: base,
                        stock_quantity: 0,
                        attribute_values: []
                    }];
                    return;
                }

                // Cartesian product
                const arrays = validRows.map(r => r.values.map(v => ({
                    attribute_id: r.attribute_id,
                    value: v.value
                })));

                const combinations = arrays.reduce((acc, curr) => {
                    const res = [];
                    acc.forEach(a => {
                        curr.forEach(b => {
                            res.push([...a, b]);
                        });
                    });
                    return res;
                }, [[]]);

                // Map combinations to variant rows, preserving custom prices if set
                this.generatedVariants = combinations.map(comb => {
                    const title = comb.map(item => item.value).join(' - ');
                    const key = comb.map(item => item.attribute_id + ':' + item.value).join('_');

                    const existing = this.generatedVariants.find(v => v.key === key);

                    return {
                        key: key,
                        title: title,
                        isCustomPrice: existing ? !!existing.isCustomPrice : false,
                        price: (existing && existing.isCustomPrice) ? existing.price : base,
                        stock_quantity: existing ? existing.stock_quantity : 0,
                        attribute_values: comb
                    };
                });
            },

            // Sync base price to variants
            syncBasePriceToVariants() {
                const base = this.parseCurrencyInput(this.form.base_price);
                this.generatedVariants.forEach(v => {
                    if (!v.isCustomPrice) {
                        v.price = base;
                    }
                });
            },

            // Submit product form (Save or Save and Add New)
            async submitProductForm(isSaveAndAdd) {
                this.formError = '';

                if (!this.form.name.trim()) {
                    this.formError = 'Vui lòng nhập tên sản phẩm.';
                    return;
                }

                if (!this.form.category_id) {
                    this.formError = 'Vui lòng chọn danh mục cho sản phẩm.';
                    return;
                }

                if (this.form.base_price === '' || isNaN(this.form.base_price) || parseFloat(this.form.base_price) < 0) {
                    this.formError = 'Vui lòng nhập giá gốc sản phẩm hợp lệ.';
                    return;
                }

                this.isSubmitting = true;

                try {
                    const formData = new FormData();
                    formData.append('name', this.form.name);
                    formData.append('category_id', this.form.category_id);
                    formData.append('base_price', this.form.base_price);
                    formData.append('description', this.form.description || '');

                    // Multi-images upload (Tối đa 5 ảnh)
                    this.newImages.forEach((item, idx) => {
                        formData.append('images[]', item.file);
                        if (item.is_primary) {
                            formData.append('primary_new_image_index', idx);
                        }
                    });

                    this.deletedImageIds.forEach(id => {
                        formData.append('deleted_image_ids[]', id);
                    });

                    const primaryExisting = this.existingImages.find(img => img.is_primary);
                    if (primaryExisting) {
                        formData.append('primary_image_id', primaryExisting.id);
                    }

                    // Append variants and their attribute values
                    this.generatedVariants.forEach((v, idx) => {
                        if (v.id) formData.append(`variants[${idx}][id]`, v.id);
                        const vPrice = (v.price !== null && v.price !== undefined && v.price !== '') ? v.price : (this.form.base_price || 0);
                        formData.append(`variants[${idx}][price]`, vPrice);
                        formData.append(`variants[${idx}][stock_quantity]`, v.stock_quantity || 0);

                        // Attach attribute values (sent on submit to be saved to DB)
                        if (v.attribute_values && v.attribute_values.length) {
                            v.attribute_values.forEach((av, aIdx) => {
                                formData.append(`variants[${idx}][attribute_values][${aIdx}][attribute_id]`, av.attribute_id);
                                formData.append(`variants[${idx}][attribute_values][${aIdx}][value]`, av.value);
                            });
                        }
                    });

                    let url = '/admin/products';
                    if (this.isEditMode && this.form.id) {
                        url = `/admin/products/${this.form.id}`;
                        formData.append('_method', 'PUT');
                    }

                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const data = await res.json();

                    if (!data.success) {
                        this.formError = data.message || 'Lỗi lưu sản phẩm.';
                        return;
                    }

                    alert(data.message);

                    if (isSaveAndAdd) {
                        if (data.next_code) {
                            this.nextCode = data.next_code;
                        }
                        this.openCreateModal();
                    } else {
                        window.location.reload();
                    }
                } catch (e) {
                    this.formError = 'Lỗi gửi dữ liệu: ' + e.message;
                } finally {
                    this.isSubmitting = false;
                }
            },

            // Toggle Product Status via AJAX
            async toggleProductStatus(productId, checkbox) {
                try {
                    const res = await fetch(`/admin/products/${productId}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json();
                    if (!data.success) {
                        alert(data.message || 'Không thể cập nhật trạng thái.');
                        checkbox.checked = !checkbox.checked;
                    }
                } catch (e) {
                    alert('Lỗi cập nhật trạng thái: ' + e.message);
                    checkbox.checked = !checkbox.checked;
                }
            },

            // Toggle Product COD via AJAX
            async toggleProductCod(productId, checkbox) {
                try {
                    const res = await fetch(`/admin/products/${productId}/toggle-cod`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json();
                    if (!data.success) {
                        alert(data.message || 'Không thể cập nhật COD.');
                        checkbox.checked = !checkbox.checked;
                    }
                } catch (e) {
                    alert('Lỗi cập nhật COD: ' + e.message);
                    checkbox.checked = !checkbox.checked;
                }
            },

            // Sao chép link sản phẩm ra website bán hàng
            copyProductLink(productId, productName) {
                const url = `${window.location.origin}/?product=${productId}#san-pham-${productId}`;
                const finishCopy = () => {
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: `Đã sao chép link chia sẻ: ${productName || ('Sản phẩm #' + productId)}` }
                    }));
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(url)
                        .then(finishCopy)
                        .catch(() => this.fallbackCopyText(url, finishCopy));
                } else {
                    this.fallbackCopyText(url, finishCopy);
                }
            },

            fallbackCopyText(text, callback) {
                try {
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-999999px';
                    textArea.style.top = '-999999px';
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    if (typeof callback === 'function') callback();
                } catch (e) {
                    prompt('Sao chép liên kết sản phẩm bên dưới:', text);
                }
            }
        };
    }
</script>
@endpush
