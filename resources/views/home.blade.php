@extends('layouts.app')

@section('title', 'Hương Mít - Thiên đường cho Mẹ & Bé yêu')
@section('meta_description', 'Hương Mít - Cung cấp sữa bột dinh dưỡng, tã bỉm, bình sữa và thực phẩm ăn dặm cho mẹ và bé.')

@section('content')

{{-- ── HERO SECTION ── --}}
<section class="relative overflow-hidden bg-gradient-to-b from-primary-50/60 via-milk to-milk pt-8 pb-14 sm:pb-20">
    <div class="absolute -top-32 -right-32 w-96 h-96 bg-primary-200/30 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 left-10 w-72 h-72 bg-peach/20 rounded-full blur-2xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            
            <div class="lg:col-span-7 space-y-5 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-primary-200 text-primary text-xs font-bold shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-primary animate-ping"></span>
                    <span>Sản phẩm chất lượng cho Mẹ & Bé</span>
                </div>

                <h1 class="text-3xl sm:text-5xl lg:text-5xl font-display font-extrabold text-gray-800 leading-tight">
                    Chăm sóc bé yêu trọn vẹn với <br class="hidden sm:inline">
                    <span class="text-gradient">Hương Mít</span>
                </h1>

                <!-- Search form embedded in Hero -->
                <form method="GET" action="{{ route('home') }}#danh-sach-san-pham" 
                      class="pt-2 max-w-lg mx-auto lg:mx-0 flex items-center gap-2 bg-white p-2 rounded-2xl border border-primary-200 shadow-sm focus-within:ring-2 focus-within:ring-primary/20">
                    <div class="pl-2 text-primary">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Tìm kiếm theo tên sữa, tã bỉm, mã SP..." 
                           class="flex-1 px-2 py-2 text-xs sm:text-sm text-gray-800 placeholder-gray-400 outline-none bg-transparent">
                    <button type="submit" 
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-primary to-primary-600 text-white font-bold text-xs sm:text-sm shadow-xs hover:shadow-md transition-all">
                        Tìm kiếm
                    </button>
                </form>
            </div>

            <!-- Hero Banner Mockup / Image -->
            <div class="lg:col-span-5 flex justify-center">
                <div class="relative w-full max-w-sm">
                    <div class="w-full aspect-square rounded-3xl bg-gradient-to-br from-primary-100 to-peach-light border-4 border-white shadow-xl overflow-hidden flex items-center justify-center p-3 sm:p-4 group">
                        <img src="{{ asset('images/hero-mother-baby.jpg') }}" 
                             alt="Hương Mít - Chăm sóc Mẹ và Bé yêu" 
                             class="w-full h-full object-cover rounded-2xl group-hover:scale-105 transition-transform duration-500">
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ── CATEGORIES BAR (FILTER BY CATEGORY) ── --}}
<section id="danh-muc-san-pham" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-lg sm:text-xl font-display font-bold text-gray-800 flex items-center gap-2">
                <span>Danh mục sản phẩm</span>
                <span class="w-2 h-2 rounded-full bg-primary"></span>
            </h2>
            <p class="text-xs text-gray-500">Chọn danh mục để xem nhanh các sản phẩm tương ứng</p>
        </div>

        @if(request('category_id') || request('search'))
            <a href="{{ route('home') }}#danh-sach-san-pham" 
               class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                <span>Bỏ lọc tất cả</span>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
        @endif
    </div>

    <!-- Category Pills Container with Hover Scrollbar & Navigation -->
    <div class="relative group" 
         x-data="{
             canScrollLeft: false,
             canScrollRight: false,
             checkScroll() {
                 const el = this.$refs.catList;
                 if (!el) return;
                 this.canScrollLeft = el.scrollLeft > 10;
                 this.canScrollRight = el.scrollLeft < (el.scrollWidth - el.clientWidth - 10);
             },
             scroll(direction) {
                 const el = this.$refs.catList;
                 if (!el) return;
                 const amount = direction === 'left' ? -260 : 260;
                 el.scrollBy({ left: amount, behavior: 'smooth' });
                 setTimeout(() => this.checkScroll(), 300);
             },
             handleWheel(e) {
                 const el = this.$refs.catList;
                 if (!el) return;
                 if (Math.abs(e.deltaX) < Math.abs(e.deltaY)) {
                     el.scrollLeft += e.deltaY;
                     e.preventDefault();
                     this.checkScroll();
                 }
             }
         }"
         x-init="$nextTick(() => { checkScroll(); }); window.addEventListener('resize', () => checkScroll());">

        <!-- Left Navigation Arrow (shown on group hover when scrollable) -->
        <button type="button" 
                x-show="canScrollLeft"
                x-cloak
                @click="scroll('left')"
                title="Xem danh mục trước"
                class="absolute -left-3 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white text-gray-700 hover:text-primary shadow-lg border border-slate-200/90 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-200 hover:scale-110 active:scale-95 cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        <!-- Right Navigation Arrow (shown on group hover when scrollable) -->
        <button type="button" 
                x-show="canScrollRight"
                x-cloak
                @click="scroll('right')"
                title="Xem danh mục tiếp theo"
                class="absolute -right-3 top-1/2 -translate-y-1/2 z-10 w-9 h-9 rounded-full bg-white text-gray-700 hover:text-primary shadow-lg border border-slate-200/90 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-200 hover:scale-110 active:scale-95 cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>

        <!-- Category Pills List (Hover scrollbar: hidden normally, 5px bar on hover) -->
        <div x-ref="catList"
             @scroll.passive="checkScroll()"
             @wheel="handleWheel($event)"
             class="flex items-center gap-2.5 overflow-x-auto pb-2 hover-scrollbar scroll-smooth">
            <!-- "Tất cả" Pill -->
            <a href="{{ route('home', array_merge(request()->except('category_id', 'page'))) }}#danh-sach-san-pham"
               class="flex-shrink-0 px-4 py-2.5 rounded-2xl text-xs font-bold transition-all duration-200 border hover:-translate-y-0.5 {{ !request('category_id') ? 'bg-primary text-white border-primary shadow-sm shadow-primary/30' : 'bg-white text-gray-700 border-slate-200 hover:border-primary-300 hover:bg-primary-50 hover:shadow-xs' }}">
                <span>Tất cả sản phẩm</span>
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ !request('category_id') ? 'bg-white/20 text-white' : 'bg-slate-100 text-gray-500' }}">
                    {{ $totalActiveProducts }}
                </span>
            </a>

            @foreach($categories as $cat)
                <a href="{{ route('home', array_merge(request()->except('page'), ['category_id' => $cat->id])) }}#danh-sach-san-pham"
                   class="flex-shrink-0 px-4 py-2.5 rounded-2xl text-xs font-bold transition-all duration-200 border hover:-translate-y-0.5 {{ request('category_id') == $cat->id ? 'bg-primary text-white border-primary shadow-sm shadow-primary/30' : 'bg-white text-gray-700 border-slate-200 hover:border-primary-300 hover:bg-primary-50 hover:shadow-xs' }}">
                    <span>{{ $cat->name }}</span>
                    <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ request('category_id') == $cat->id ? 'bg-white/20 text-white' : 'bg-slate-100 text-gray-500' }}">
                        {{ $cat->products_count }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ── PRODUCTS SECTION (SEARCH, SORT & CARDS LIST) ── --}}
<section id="danh-sach-san-pham" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 mb-16" 
         x-data="shopManager()"
         @view-product.window="openProductModal($event.detail.id, false)">

    <!-- Filter & Sort Bar -->
    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-primary-100/80 shadow-xs mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            
            <!-- Result Title / Active Filter -->
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-primary-50 text-primary flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-gray-800 font-display">
                        @if(request('search'))
                            Kết quả tìm kiếm cho: "<span class="text-primary">{{ request('search') }}</span>"
                        @elseif(request('category_id'))
                            @php
                                $selectedCategory = $categories->firstWhere('id', request('category_id'));
                            @endphp
                            Danh mục: <span class="text-primary">{{ $selectedCategory->name ?? '' }}</span>
                        @else
                            Tất cả sản phẩm mẹ & bé
                        @endif
                    </h3>
                    <p class="text-xs text-gray-400">Hiển thị {{ $products->total() }} sản phẩm đang bán</p>
                </div>
            </div>

            <!-- Sorting & Search Form -->
            <form method="GET" action="{{ route('home') }}#danh-sach-san-pham" class="flex flex-wrap items-center gap-2.5">
                @if(request('category_id'))
                    <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                @endif

                <!-- Inline Search -->
                <div class="relative min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Lọc theo tên SP..." 
                           class="w-full pl-8 pr-3 py-2 rounded-xl border border-slate-200 text-xs text-gray-800 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Sort select -->
                <div class="w-40">
                    <select name="sort" onchange="this.form.submit()"
                            class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs text-gray-700 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none cursor-pointer">
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Giá: Thấp đến Cao</option>
                        <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Giá: Cao đến Thấp</option>
                    </select>
                </div>

                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition-colors">
                    Lọc
                </button>
            </form>

        </div>
    </div>

    <!-- Products Cards Grid -->
    @if($products->isEmpty())
        <div class="bg-white rounded-3xl p-12 text-center border border-primary-100 shadow-xs">
            <div class="w-20 h-20 rounded-full bg-primary-50 flex items-center justify-center mx-auto mb-4 text-primary">
                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h4 class="font-display font-bold text-lg text-gray-800 mb-1">Không tìm thấy sản phẩm phù hợp</h4>
            <p class="text-xs text-gray-400 mb-6">Thử tìm kiếm với từ khóa khác hoặc bỏ chọn bộ lọc danh mục.</p>
            <a href="{{ route('home') }}" class="px-6 py-2.5 rounded-2xl bg-primary text-white font-bold text-xs hover:bg-primary-600 transition-all shadow-xs">
                Xem tất cả sản phẩm
            </a>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
            @foreach($products as $p)
                <!-- Product Card -->
                <div id="san-pham-{{ $p->id }}"
                     class="group bg-white rounded-3xl border border-slate-200/80 hover:border-primary-300 shadow-xs hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col overflow-hidden cursor-pointer"
                     @click="openProductModal({{ $p->id }})">
                    
                    <!-- Card Thumbnail Header -->
                    <div class="relative w-full aspect-square bg-milk overflow-hidden flex items-center justify-center p-3">
                        @if($p->primary_image_url)
                            <img src="{{ $p->primary_image_url }}" alt="{{ $p->name }}" 
                                 class="w-full h-full object-cover rounded-2xl group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="w-full h-full rounded-2xl bg-primary-50/50 flex items-center justify-center text-primary/40">
                                <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif

                        <!-- Sale Badge -->
                        @if($p->active_sale_item)
                            <span class="absolute top-3 left-3 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-gradient-to-r from-rose-500 to-amber-500 text-white shadow-xs flex items-center gap-1 animate-pulse">
                                🔥 SALE -{{ $p->active_sale_item->discount_percent }}%
                            </span>
                        @endif

                        <!-- Variants Count Badge -->
                        @if($p->variants->count() > 1)
                            <span class="absolute top-3 right-3 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-900/80 backdrop-blur-xs text-white shadow-xs">
                                {{ $p->variants->count() }} phân loại
                            </span>
                        @endif
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <!-- Category Name -->
                            <span class="text-[10px] font-bold uppercase tracking-wider text-primary block mb-1">
                                {{ $p->category->name ?? 'Đồ Mẹ & Bé' }}
                            </span>

                            <!-- Product Name -->
                            <h4 class="font-bold text-gray-800 text-xs sm:text-sm line-clamp-2 leading-snug group-hover:text-primary transition-colors mb-1.5">
                                {{ $p->name }}
                            </h4>

                            <!-- Product Description -->
                            @if(!empty($p->description))
                                <p class="text-[11px] text-gray-500 line-clamp-2 leading-relaxed mb-2 font-normal">
                                    {{ $p->description }}
                                </p>
                            @endif
                        </div>

                        <div class="pt-2 border-t border-slate-100">
                            <!-- Main Product Price (Giá theo sản phẩm chính / Flash Sale) -->
                            <div class="mb-3">
                                @if($p->active_sale_item)
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-[10px] font-bold text-rose-500 flex items-center gap-1">
                                            🔥 Giá Sale:
                                        </span>
                                        <span class="text-[10px] line-through text-slate-400 font-medium">
                                            {{ number_format($p->active_sale_item->original_price, 0, ',', '.') }} đ
                                        </span>
                                    </div>
                                    <div class="text-base sm:text-lg font-extrabold text-rose-600 font-sans leading-none">
                                        {{ number_format($p->active_sale_item->sale_price, 0, ',', '.') }} đ
                                    </div>
                                    <div class="text-[9px] text-amber-600 font-bold mt-1">
                                        Chỉ còn {{ $p->active_sale_item->remaining_quantity }} suất sale
                                    </div>
                                @else
                                    <span class="text-[10px] text-gray-400 block font-medium">Giá sản phẩm chính:</span>
                                    <div class="text-base sm:text-lg font-extrabold text-primary font-sans leading-none">
                                        {{ number_format($p->base_price, 0, ',', '.') }} đ
                                    </div>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-2 gap-1.5" @click.stop>
                                <!-- Thêm vào giỏ Button -->
                                <button type="button" @click="openProductModal({{ $p->id }}, false)"
                                        title="Thêm vào giỏ hàng"
                                        class="py-2 px-2.5 rounded-xl border border-primary-200 bg-primary-50/60 hover:bg-primary-100 text-primary font-bold text-[11px] flex items-center justify-center gap-1 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                    <span class="hidden sm:inline">Thêm giỏ</span>
                                </button>

                                <!-- Mua ngay Button -->
                                <button type="button" @click="openProductModal({{ $p->id }}, true)"
                                        title="Mua ngay"
                                        class="py-2 px-2.5 rounded-xl bg-gradient-to-r from-primary to-primary-600 hover:from-primary-600 hover:to-primary text-white font-bold text-[11px] flex items-center justify-center gap-1 shadow-xs hover:shadow-md transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    <span>Mua ngay</span>
                                </button>
                            </div>
                        </div>

                    </div>

                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-10 flex justify-center">
            {{ $products->links() }}
        </div>
    @endif

    {{-- ── PRODUCT QUICK-VIEW & VARIANT SELECTION POPUP ── --}}
    <template x-teleport="body">
        <div x-show="modalOpen" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6"
             role="dialog" aria-modal="true">
            
            <!-- Backdrop -->
            <div x-show="modalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closeModal()"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

            <!-- Modal Panel -->
            <div x-show="modalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                 class="relative bg-white rounded-3xl max-w-3xl w-full shadow-2xl border border-primary-100 overflow-hidden z-10 max-h-[90vh] flex flex-col">
                
                <!-- Close Button -->
                <button type="button" @click="closeModal()"
                        class="absolute top-4 right-4 z-20 w-8 h-8 rounded-full bg-white/90 hover:bg-primary-50 text-gray-500 hover:text-primary flex items-center justify-center shadow-sm border border-slate-200 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <!-- Loading State in Modal -->
                <div x-show="loading" class="py-24 text-center">
                    <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                    <p class="text-xs font-semibold text-gray-500">Đang tải thông tin sản phẩm...</p>
                </div>

                <!-- Modal Content when loaded -->
                <div x-show="!loading && currentProduct" class="overflow-y-auto p-6 sm:p-8">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 sm:gap-8 items-start">
                        
                        <!-- Left: Images Gallery & Description (5 cols) -->
                        <div class="md:col-span-5 space-y-3">
                            <div @click="openZoom()"
                                 title="Nhấn để phóng to ảnh"
                                 class="w-full aspect-square rounded-2xl bg-milk border border-slate-200/80 overflow-hidden flex items-center justify-center p-2 shadow-xs relative group cursor-zoom-in">
                                <img :src="activeImage" :alt="currentProduct?.name" 
                                     class="w-full h-full object-cover rounded-xl transition-all duration-300 group-hover:scale-[1.02]">

                                <!-- Hover Badge / Zoom prompt -->
                                <div class="absolute bottom-3 right-3 bg-white/90 hover:bg-white backdrop-blur-md text-slate-700 text-[11px] font-semibold px-2.5 py-1.5 rounded-full shadow-sm flex items-center gap-1.5 opacity-90 group-hover:opacity-100 group-hover:bg-primary group-hover:text-white transition-all pointer-events-none">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                    </svg>
                                    <span>Nhấn để phóng to</span>
                                </div>
                            </div>

                            <!-- Thumbnails -->
                            <template x-if="currentProduct?.images && currentProduct.images.length > 1">
                                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                    <template x-for="img in currentProduct.images" :key="img.id">
                                        <button type="button" @click="activeImage = img.image_url"
                                                class="w-14 h-14 rounded-xl border-2 overflow-hidden flex-shrink-0 transition-all p-0.5"
                                                :class="activeImage === img.image_url ? 'border-primary ring-2 ring-primary/20' : 'border-slate-200 hover:border-primary-200'">
                                            <img :src="img.image_url" class="w-full h-full object-cover rounded-lg">
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <!-- Product Description (Bên dưới ảnh) -->
                            <template x-if="currentProduct?.description">
                                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700">
                                        <svg class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Mô tả sản phẩm</span>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line max-h-48 overflow-y-auto pr-1" x-text="currentProduct?.description"></p>
                                </div>
                            </template>
                        </div>

                        <!-- Right: Info, Variant Options, Quantity, Actions (7 cols) -->
                        <div class="md:col-span-7 space-y-4">
                            
                            <!-- Badges -->
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-primary-100 text-primary"
                                      x-text="currentProduct?.category?.name || 'Mẹ & Bé'"></span>
                            </div>

                            <!-- Title -->
                            <h3 class="text-base sm:text-xl font-display font-bold text-gray-800 leading-snug"
                                x-text="currentProduct?.name"></h3>

                            <!-- Dynamic Price Display (Hỗ trợ Flash Sale) -->
                            <div class="p-3.5 rounded-2xl border transition-all"
                                 :class="selectedVariant?.active_sale_item ? 'bg-gradient-to-r from-rose-50 to-amber-50 border-rose-200' : 'bg-primary-50/50 border-primary-100'">
                                <div class="flex items-baseline justify-between">
                                    <div>
                                        <template x-if="selectedVariant?.active_sale_item">
                                            <div>
                                                <div class="flex items-center gap-1.5 mb-1">
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-rose-600 text-white flex items-center gap-1 animate-pulse">
                                                        🔥 FLASH SALE
                                                    </span>
                                                    <span class="text-[10px] text-rose-700 font-bold" x-text="selectedVariant.active_sale_item.campaign?.name"></span>
                                                </div>
                                                <div class="flex items-baseline gap-2">
                                                    <span class="text-2xl font-extrabold text-rose-600 font-sans"
                                                          x-text="formatCurrency(selectedVariant.active_sale_item.sale_price)"></span>
                                                    <span class="text-xs line-through text-slate-400 font-semibold"
                                                          x-text="formatCurrency(selectedVariant.price)"></span>
                                                </div>
                                                <div class="text-[10px] text-amber-700 font-bold mt-0.5">
                                                    Đã bán <span x-text="selectedVariant.active_sale_item.sold_quantity || 0"></span>/<span x-text="selectedVariant.active_sale_item.sale_quantity"></span> suất (Còn <strong x-text="selectedVariant.active_sale_item.remaining_quantity"></strong> suất)
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="!selectedVariant?.active_sale_item">
                                            <div>
                                                <span class="text-[10px] text-gray-400 block font-medium">Giá sản phẩm theo lựa chọn:</span>
                                                <span class="text-2xl font-extrabold text-primary font-sans"
                                                      x-text="formatCurrency(selectedVariant ? selectedVariant.price : currentProduct?.base_price)"></span>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Stock Status -->
                                    <div class="text-right">
                                        <template x-if="selectedVariant && selectedVariant.stock_quantity > 0">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span>Còn <strong x-text="selectedVariant.stock_quantity"></strong> chiếc</span>
                                            </span>
                                        </template>
                                        <template x-if="selectedVariant && selectedVariant.stock_quantity <= 0">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                                                <span>Hết hàng</span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Variant Attribute Selectors (Thuộc tính & Biến thể) -->
                            <div class="space-y-3.5 pt-1">
                                <template x-for="attr in groupedAttributes" :key="attr.id">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-bold text-gray-700" x-text="attr.name + ':'"></span>
                                            <span class="text-[11px] text-primary font-bold" x-text="selectedOptions[attr.id]"></span>
                                        </div>

                                        <!-- Option Pills -->
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="val in attr.values" :key="val">
                                                <button type="button" @click="selectOption(attr.id, val)"
                                                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border transition-all duration-150 flex items-center gap-1.5"
                                                        :class="selectedOptions[attr.id] === val 
                                                            ? 'border-primary bg-primary text-white shadow-xs font-bold' 
                                                            : 'border-slate-200 bg-white hover:border-primary-300 text-gray-700 hover:bg-primary-50/50'">
                                                    <span x-text="val"></span>
                                                    <svg x-show="selectedOptions[attr.id] === val" class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Selected Variant Title -->
                            <div class="text-xs text-gray-500 flex items-center gap-1.5 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                <span class="font-bold text-gray-700">Đã chọn:</span>
                                <span class="font-semibold text-primary" x-text="selectedVariant ? selectedVariant.variant_title : 'Biến thể mặc định'"></span>
                            </div>

                            <!-- Quantity Selector -->
                            <div class="flex items-center gap-4 pt-1">
                                <label class="text-xs font-bold text-gray-700">Số lượng:</label>
                                <div class="flex items-center border border-slate-200 rounded-xl bg-white overflow-hidden shadow-xs">
                                    <button type="button" @click="if (quantity > 1) quantity--"
                                            :disabled="quantity <= 1"
                                            class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-primary-50 hover:text-primary disabled:opacity-30">
                                        -
                                    </button>
                                    <input type="number" x-model.number="quantity" min="1" 
                                           :max="selectedVariant?.stock_quantity || 99"
                                           class="w-12 text-center text-xs font-bold text-gray-800 outline-none border-x border-slate-200 py-1">
                                    <button type="button" @click="if (!selectedVariant || quantity < selectedVariant.stock_quantity) quantity++"
                                            :disabled="selectedVariant && quantity >= selectedVariant.stock_quantity"
                                            class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-primary-50 hover:text-primary disabled:opacity-30">
                                        +
                                    </button>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-2 gap-3 pt-3">
                                <!-- Thêm vào giỏ hàng -->
                                <button type="button" @click="handleAddToCart(false)"
                                        :disabled="isSubmitting || (selectedVariant && selectedVariant.stock_quantity <= 0)"
                                        class="py-3 px-4 rounded-2xl border-2 border-primary text-primary hover:bg-primary-50 font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                    <span>Thêm vào giỏ</span>
                                </button>

                                <!-- Mua ngay -->
                                <button type="button" @click="handleAddToCart(true)"
                                        :disabled="isSubmitting || (selectedVariant && selectedVariant.stock_quantity <= 0)"
                                        class="py-3 px-4 rounded-2xl bg-gradient-to-r from-primary to-primary-600 hover:from-primary-600 hover:to-primary text-white font-bold text-xs flex items-center justify-center gap-2 shadow-md hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    <span>Mua ngay</span>
                                </button>
                            </div>

                        </div>

                    </div>
                </div>

            </div>
        </div>
    </template>

    {{-- ── FULLSCREEN IMAGE ZOOM & LIGHTBOX MODAL ── --}}
    <template x-teleport="body">
        <div x-show="zoomOpen" 
             x-cloak
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="if (zoomOpen) closeZoom()"
             @keydown.arrow-left.window="if (zoomOpen) prevImage()"
             @keydown.arrow-right.window="if (zoomOpen) nextImage()"
             @keydown.equal.window="if (zoomOpen) zoomIn()"
             @keydown.minus.window="if (zoomOpen) zoomOut()"
             class="fixed inset-0 z-[70] flex flex-col justify-between bg-slate-950/92 backdrop-blur-md select-none overflow-hidden"
             role="dialog" aria-modal="true">

            <!-- Top Bar: Title, Count, Zoom Controls, Close -->
            <div class="relative z-20 flex items-center justify-between px-4 sm:px-6 py-3.5 bg-gradient-to-b from-black/75 to-transparent text-white">
                <div class="flex items-center gap-3 min-w-0 pr-4">
                    <h4 class="text-sm sm:text-base font-semibold truncate text-white/90"
                        x-text="currentProduct?.name"></h4>
                    <span class="hidden sm:inline-block text-xs text-white/60 font-mono"
                          x-show="getImageList().length > 1"
                          x-text="'(' + (getCurrentImageIndex() + 1) + ' / ' + getImageList().length + ')'"></span>
                </div>

                <!-- Controls -->
                <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
                    <!-- Zoom Out -->
                    <button type="button" 
                            @click="zoomOut()" 
                            :disabled="zoomLevel <= 1"
                            title="Thu nhỏ (-)"
                            class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 disabled:opacity-30 disabled:hover:bg-white/10 text-white flex items-center justify-center transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                    </button>

                    <!-- Zoom Level Pill / Reset -->
                    <button type="button" 
                            @click="resetZoom()" 
                            title="Đặt lại kích thước (100%)"
                            class="px-3 h-9 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 text-white text-xs font-mono font-bold flex items-center justify-center transition-all cursor-pointer">
                        <span x-text="Math.round(zoomLevel * 100) + '%'"></span>
                    </button>

                    <!-- Zoom In -->
                    <button type="button" 
                            @click="zoomIn()" 
                            :disabled="zoomLevel >= 4"
                            title="Phóng to (+)"
                            class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 active:scale-95 disabled:opacity-30 disabled:hover:bg-white/10 text-white flex items-center justify-center transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>

                    <div class="h-6 w-px bg-white/20 mx-1"></div>

                    <!-- Close Button -->
                    <button type="button" 
                            @click="closeZoom()" 
                            title="Đóng (Esc)"
                            class="w-9 h-9 rounded-xl bg-white/10 hover:bg-rose-500/80 active:scale-95 text-white flex items-center justify-center transition-all cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Main Stage (Image Viewport) -->
            <div class="relative flex-1 flex items-center justify-center overflow-hidden w-full h-full"
                 @click="handleBackdropClick($event)">

                <!-- Prev Button -->
                <button type="button" 
                        x-show="getImageList().length > 1"
                        @click.stop="prevImage()" 
                        class="absolute left-3 sm:left-6 z-20 w-11 h-11 rounded-full bg-black/50 hover:bg-black/80 text-white/90 hover:text-white backdrop-blur-xs flex items-center justify-center shadow-lg transition-all hover:scale-110 active:scale-95 cursor-pointer"
                        title="Ảnh trước (←)">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <!-- Next Button -->
                <button type="button" 
                        x-show="getImageList().length > 1"
                        @click.stop="nextImage()" 
                        class="absolute right-3 sm:right-6 z-20 w-11 h-11 rounded-full bg-black/50 hover:bg-black/80 text-white/90 hover:text-white backdrop-blur-xs flex items-center justify-center shadow-lg transition-all hover:scale-110 active:scale-95 cursor-pointer"
                        title="Ảnh tiếp theo (→)">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                <!-- Draggable / Scalable Image Container -->
                <div class="relative max-w-full max-h-full flex items-center justify-center p-4 pointer-events-auto"
                     @wheel.prevent="handleWheel($event)"
                     @mousedown="startDrag($event)"
                     @mousemove="onDrag($event)"
                     @mouseup="stopDrag()"
                     @mouseleave="stopDrag()"
                     @touchstart="handleTouchStart($event)"
                     @touchmove="handleTouchMove($event)"
                     @touchend="handleTouchEnd()">
                    
                    <img :src="activeImage" 
                         :alt="currentProduct?.name"
                         @click.stop="toggleZoomClick($event)"
                         :style="`transform: translate(${panX}px, ${panY}px) scale(${zoomLevel}); cursor: ${zoomLevel > 1 ? (isDragging ? 'grabbing' : 'grab') : 'zoom-in'};`"
                         class="max-w-[85vw] max-h-[75vh] object-contain rounded-2xl select-none transition-transform duration-75 will-change-transform shadow-2xl"
                         draggable="false">
                </div>

                <!-- Helpful Hint Overlay -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 pointer-events-none px-3.5 py-1.5 rounded-full bg-black/60 backdrop-blur-xs text-[11px] text-white/80 font-medium flex items-center gap-2 shadow-sm text-center">
                    <span x-show="zoomLevel === 1">💡 Nhấn vào ảnh để phóng to 2.25x hoặc cuộn chuột để zoom</span>
                    <span x-show="zoomLevel > 1">💡 Giữ chuột & kéo để di chuyển ảnh • Cuộn chuột để chỉnh độ phóng</span>
                </div>
            </div>

            <!-- Bottom Thumbnails Strip -->
            <div class="relative z-20 px-4 py-3 bg-gradient-to-t from-black/80 via-black/50 to-transparent flex justify-center"
                 x-show="getImageList().length > 1">
                <div class="flex items-center gap-2 overflow-x-auto max-w-full py-1 px-2">
                    <template x-for="(img, idx) in getImageList()" :key="img.id || idx">
                        <button type="button" 
                                @click.stop="selectZoomImage(img.image_url)"
                                class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl border-2 overflow-hidden flex-shrink-0 transition-all p-0.5 cursor-pointer"
                                :class="activeImage === img.image_url ? 'border-primary ring-2 ring-primary/50 scale-105' : 'border-white/30 hover:border-white/70 opacity-70 hover:opacity-100'">
                            <img :src="img.image_url" class="w-full h-full object-cover rounded-lg">
                        </button>
                    </template>
                </div>
            </div>

        </div>
    </template>

</section>

@endsection

@push('scripts')
<script>
function shopManager() {
    return {
        modalOpen: false,
        loading: false,
        isSubmitting: false,
        isBuyNow: false,

        currentProduct: null,
        groupedAttributes: [],
        selectedOptions: {}, // { [attribute_id]: value }
        selectedVariant: null,
        activeImage: '',
        quantity: 1,

        // Zoom Lightbox State
        zoomOpen: false,
        zoomLevel: 1,
        panX: 0,
        panY: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        initialMouseX: 0,
        initialMouseY: 0,
        hasDragged: false,
        lastTouchDistance: 0,

        formatCurrency(num) {
            return new Intl.NumberFormat('vi-VN').format(num || 0) + ' đ';
        },

        async openProductModal(productId, buyNow = false) {
            this.modalOpen = true;
            this.loading = true;
            this.isBuyNow = buyNow;
            this.quantity = 1;
            this.currentProduct = null;
            this.groupedAttributes = [];
            this.selectedOptions = {};
            this.selectedVariant = null;

            try {
                const res = await fetch(`/san-pham/${productId}/json`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                
                if (!data.success || !data.product) {
                    alert(data.message || 'Không thể tải thông tin sản phẩm.');
                    this.closeModal();
                    return;
                }

                this.currentProduct = data.product;
                this.groupedAttributes = data.grouped_attributes || [];
                
                // Set default image
                this.activeImage = (data.product.primary_image && data.product.primary_image.image_url) 
                    ? data.product.primary_image.image_url 
                    : (data.product.images && data.product.images.length ? data.product.images[0].image_url : '');

                // Automatically select first available variant combination
                if (data.product.variants && data.product.variants.length > 0) {
                    const firstVariant = data.product.variants[0];
                    if (firstVariant.attribute_values && firstVariant.attribute_values.length > 0) {
                        firstVariant.attribute_values.forEach(av => {
                            this.selectedOptions[av.attribute_id] = av.value;
                        });
                    }
                    this.matchSelectedVariant();
                }

            } catch (e) {
                alert('Lỗi: ' + e.message);
                this.closeModal();
            } finally {
                this.loading = false;
            }
        },

        closeModal() {
            this.closeZoom();
            this.modalOpen = false;
        },

        // Zoom & Lightbox Controls
        openZoom(imageUrl = null) {
            if (imageUrl) {
                this.activeImage = imageUrl;
            }
            if (!this.activeImage) return;
            this.zoomOpen = true;
            this.resetZoom();
        },

        closeZoom() {
            this.zoomOpen = false;
            this.resetZoom();
        },

        zoomIn() {
            this.zoomLevel = Math.min(4, Math.round((this.zoomLevel + 0.5) * 10) / 10);
        },

        zoomOut() {
            this.zoomLevel = Math.max(1, Math.round((this.zoomLevel - 0.5) * 10) / 10);
            if (this.zoomLevel <= 1) {
                this.panX = 0;
                this.panY = 0;
            }
        },

        resetZoom() {
            this.zoomLevel = 1;
            this.panX = 0;
            this.panY = 0;
            this.isDragging = false;
            this.hasDragged = false;
        },

        toggleZoomClick(e) {
            if (this.hasDragged) {
                return;
            }
            if (this.zoomLevel === 1) {
                this.zoomLevel = 2.25;
            } else {
                this.resetZoom();
            }
        },

        handleWheel(e) {
            if (e.deltaY < 0) {
                this.zoomIn();
            } else {
                this.zoomOut();
            }
        },

        startDrag(e) {
            if (this.zoomLevel <= 1) return;
            if (e.button !== 0) return;
            this.isDragging = true;
            this.hasDragged = false;
            this.dragStartX = e.clientX - this.panX;
            this.dragStartY = e.clientY - this.panY;
            this.initialMouseX = e.clientX;
            this.initialMouseY = e.clientY;
        },

        onDrag(e) {
            if (!this.isDragging) return;
            const dx = Math.abs(e.clientX - this.initialMouseX);
            const dy = Math.abs(e.clientY - this.initialMouseY);
            if (dx > 4 || dy > 4) {
                this.hasDragged = true;
            }
            this.panX = e.clientX - this.dragStartX;
            this.panY = e.clientY - this.dragStartY;
        },

        stopDrag() {
            this.isDragging = false;
            setTimeout(() => {
                this.hasDragged = false;
            }, 100);
        },

        handleTouchStart(e) {
            if (e.touches.length === 1 && this.zoomLevel > 1) {
                this.isDragging = true;
                this.hasDragged = false;
                this.dragStartX = e.touches[0].clientX - this.panX;
                this.dragStartY = e.touches[0].clientY - this.panY;
                this.initialMouseX = e.touches[0].clientX;
                this.initialMouseY = e.touches[0].clientY;
            } else if (e.touches.length === 2) {
                this.lastTouchDistance = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
            }
        },

        handleTouchMove(e) {
            if (e.touches.length === 1 && this.isDragging && this.zoomLevel > 1) {
                const dx = Math.abs(e.touches[0].clientX - this.initialMouseX);
                const dy = Math.abs(e.touches[0].clientY - this.initialMouseY);
                if (dx > 4 || dy > 4) {
                    this.hasDragged = true;
                }
                this.panX = e.touches[0].clientX - this.dragStartX;
                this.panY = e.touches[0].clientY - this.dragStartY;
            } else if (e.touches.length === 2 && this.lastTouchDistance > 0) {
                const dist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                const diff = (dist - this.lastTouchDistance) * 0.01;
                this.zoomLevel = Math.min(4, Math.max(1, Math.round((this.zoomLevel + diff) * 10) / 10));
                this.lastTouchDistance = dist;
            }
        },

        handleTouchEnd() {
            this.isDragging = false;
            this.lastTouchDistance = 0;
            if (this.zoomLevel <= 1) {
                this.panX = 0;
                this.panY = 0;
            }
            setTimeout(() => {
                this.hasDragged = false;
            }, 100);
        },

        handleBackdropClick(e) {
            if (e.target === e.currentTarget && this.zoomLevel === 1) {
                this.closeZoom();
            }
        },

        getImageList() {
            if (this.currentProduct && Array.isArray(this.currentProduct.images) && this.currentProduct.images.length > 0) {
                return this.currentProduct.images;
            }
            if (this.activeImage) {
                return [{ id: 0, image_url: this.activeImage }];
            }
            return [];
        },

        getCurrentImageIndex() {
            const list = this.getImageList();
            const idx = list.findIndex(img => img.image_url === this.activeImage);
            return idx >= 0 ? idx : 0;
        },

        prevImage() {
            const list = this.getImageList();
            if (list.length <= 1) return;
            const currentIdx = this.getCurrentImageIndex();
            const prevIdx = (currentIdx - 1 + list.length) % list.length;
            this.activeImage = list[prevIdx].image_url;
            this.resetZoom();
        },

        nextImage() {
            const list = this.getImageList();
            if (list.length <= 1) return;
            const currentIdx = this.getCurrentImageIndex();
            const nextIdx = (currentIdx + 1) % list.length;
            this.activeImage = list[nextIdx].image_url;
            this.resetZoom();
        },

        selectZoomImage(url) {
            this.activeImage = url;
            this.resetZoom();
        },

        selectOption(attributeId, value) {
            this.selectedOptions[attributeId] = value;
            this.matchSelectedVariant();
        },

        matchSelectedVariant() {
            if (!this.currentProduct || !this.currentProduct.variants) return;

            // If product has no attributes, take the first variant
            if (this.groupedAttributes.length === 0) {
                this.selectedVariant = this.currentProduct.variants[0] || null;
                return;
            }

            // Find variant that matches all selectedOptions
            const match = this.currentProduct.variants.find(variant => {
                if (!variant.attribute_values || variant.attribute_values.length === 0) return false;
                
                return Object.entries(this.selectedOptions).every(([attrId, optVal]) => {
                    return variant.attribute_values.some(av => av.attribute_id == attrId && av.value == optVal);
                });
            });

            this.selectedVariant = match || this.currentProduct.variants[0];
        },

        async handleAddToCart(buyNowMode = false) {
            // Check authentication first
            const isAuth = {{ Auth::check() ? 'true' : 'false' }};
            if (!isAuth) {
                // Open the sleek login requirement modal
                window.dispatchEvent(new CustomEvent('require-login', {
                    detail: { message: 'Vui lòng đăng nhập tài khoản để thêm sản phẩm vào giỏ hàng và mua sắm.' }
                }));
                // Call globalApp's showLoginModal if available
                if (typeof this.showLoginModal === 'function') {
                    this.showLoginModal('Vui lòng đăng nhập tài khoản để thêm sản phẩm vào giỏ hàng và mua sắm.');
                }
                return;
            }

            if (!this.selectedVariant) {
                alert('Vui lòng chọn biến thể sản phẩm.');
                return;
            }

            if (this.selectedVariant.stock_quantity <= 0) {
                alert('Biến thể này hiện đã hết hàng.');
                return;
            }

            this.isSubmitting = true;
            const isBuyNow = buyNowMode || this.isBuyNow;

            try {
                const res = await fetch('/gio-hang/them', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        product_id: this.currentProduct.id,
                        product_variant_id: this.selectedVariant.id,
                        quantity: this.quantity,
                        buy_now: isBuyNow ? 1 : 0
                    })
                });

                const data = await res.json();

                if (!data.success) {
                    if (data.need_login) {
                        this.showLoginModal(data.message);
                        return;
                    }
                    alert(data.message || 'Lỗi thêm vào giỏ hàng.');
                    return;
                }

                // Notify global app of updated cart
                window.dispatchEvent(new CustomEvent('cart-updated', {
                    detail: { count: data.count, total: data.total, items: data.items }
                }));

                this.closeModal();

                if (isBuyNow && data.cart_item_id) {
                    // Redirect directly to checkout screen with this product (no cart step!)
                    window.location.href = `/thanh-toan?items=${data.cart_item_id}`;
                } else {
                    // Show success toast and open drawer
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { message: data.message }
                    }));
                    window.dispatchEvent(new CustomEvent('open-cart'));
                }

            } catch (e) {
                alert('Lỗi: ' + e.message);
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>
@endpush
