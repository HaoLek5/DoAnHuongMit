@extends('layouts.admin')

@section('title', 'Thống kê & Báo cáo - Hương Mít')
@section('page_title', 'Thống kê & Báo cáo')

@section('content')
<div class="space-y-6" x-data="{ customDateOpen: {{ $period === 'custom' ? 'true' : 'false' }} }">

    {{-- Top Filter Bar --}}
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-primary/10 text-primary">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </span>
                    <h2 class="text-xl font-bold text-slate-800 tracking-tight">Báo cáo & Thống kê kinh doanh</h2>
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    Đang xem thống kê: <span class="font-bold text-primary">{{ $periodLabel }}</span>
                </p>
            </div>

            {{-- Quick Filter Pills --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('admin.statistics.index', ['period' => 'today']) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $period === 'today' ? 'bg-primary text-white shadow-sm shadow-primary/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Hôm nay
                </a>
                <a href="{{ route('admin.statistics.index', ['period' => 'yesterday']) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $period === 'yesterday' ? 'bg-primary text-white shadow-sm shadow-primary/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Hôm qua
                </a>
                <a href="{{ route('admin.statistics.index', ['period' => '7days']) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $period === '7days' ? 'bg-primary text-white shadow-sm shadow-primary/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    7 ngày qua
                </a>
                <a href="{{ route('admin.statistics.index', ['period' => '30days']) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $period === '30days' ? 'bg-primary text-white shadow-sm shadow-primary/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    30 ngày qua
                </a>
                <a href="{{ route('admin.statistics.index', ['period' => 'this_month']) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $period === 'this_month' ? 'bg-primary text-white shadow-sm shadow-primary/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Tháng này
                </a>
                <a href="{{ route('admin.statistics.index', ['period' => 'this_year']) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all {{ $period === 'this_year' ? 'bg-primary text-white shadow-sm shadow-primary/30' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Năm nay
                </a>
                <button @click="customDateOpen = !customDateOpen" 
                        type="button"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 {{ $period === 'custom' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Tùy chọn ngày</span>
                </button>
            </div>
        </div>

        {{-- Custom Date Form Dropdown --}}
        <div x-show="customDateOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-5 pt-5 border-t border-slate-100">
            <form method="GET" action="{{ route('admin.statistics.index') }}" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="period" value="custom">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Từ ngày</label>
                    <input type="date" name="from_date" 
                           value="{{ request('from_date', $startDate->format('Y-m-d')) }}"
                           class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Đến ngày</label>
                    <input type="date" name="to_date" 
                           value="{{ request('to_date', $endDate->format('Y-m-d')) }}"
                           class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <button type="submit" 
                        class="px-5 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary/90 transition-all shadow-sm shadow-primary/30 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span>Lọc dữ liệu</span>
                </button>
            </form>
        </div>
    </div>

    {{-- 4 Main KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Doanh thu thực thu -->
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Doanh thu thực thu</p>
                <div class="w-10 h-10 rounded-2xl bg-rose-50 text-primary flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($revenue) }} <span class="text-sm font-semibold text-slate-500">đ</span></h3>
                <div class="flex items-center gap-1.5 mt-2 text-xs font-semibold {{ $revenueGrowth >= 0 ? 'text-emerald-600' : 'text-rose-500' }}">
                    @if($revenueGrowth >= 0)
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                        </svg>
                        <span>+{{ $revenueGrowth }}% so với kỳ trước</span>
                    @else
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        <span>{{ $revenueGrowth }}% so với kỳ trước</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Đơn hàng thành công / Tổng đơn -->
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Đơn hàng thành công</p>
                <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">
                    {{ number_format($confirmedOrdersCount) }} 
                    <span class="text-sm font-semibold text-slate-400">/ {{ number_format($totalOrders) }} đơn</span>
                </h3>
                <div class="flex items-center gap-1.5 mt-2 text-xs font-semibold text-slate-500">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Tỷ lệ hoàn thành: <strong class="text-slate-800">{{ $totalOrders > 0 ? round(($confirmedOrdersCount / $totalOrders) * 100, 1) : 0 }}%</strong></span>
                </div>
            </div>
        </div>

        <!-- Giá trị trung bình đơn (AOV) -->
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Giá trị đơn TB (AOV)</p>
                <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($averageOrderValue) }} <span class="text-sm font-semibold text-slate-500">đ</span></h3>
                <div class="flex items-center gap-1.5 mt-2 text-xs font-semibold text-slate-500">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Doanh thu / số đơn thành công</span>
                </div>
            </div>
        </div>

        <!-- Sản phẩm đã bán & Khách mới -->
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Sản phẩm đã bán</p>
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($totalUnitsSold) }} <span class="text-sm font-semibold text-slate-500">sản phẩm</span></h3>
                <div class="flex items-center gap-1.5 mt-2 text-xs font-semibold text-emerald-600">
                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">+</span>
                    <span>{{ number_format($newCustomersCount) }} khách hàng mới đăng ký</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Chart: Biểu đồ doanh thu & đơn hàng theo thời gian --}}
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-800 tracking-tight">Biểu đồ doanh thu & số lượng đơn hàng</h3>
                <p class="text-xs text-slate-400 mt-0.5">Biểu diễn diễn biến kinh doanh trong kỳ đã chọn</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold">
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-primary inline-block"></span>
                    <span class="text-slate-600">Doanh thu (VNĐ)</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-indigo-500 inline-block"></span>
                    <span class="text-slate-600">Số đơn hàng</span>
                </div>
            </div>
        </div>

        <div class="relative w-full h-72 sm:h-80">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    {{-- Grid 2 Columns: Trạng thái đơn hàng & Phương thức thanh toán --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Order Status Breakdown --}}
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 tracking-tight">Cơ cấu trạng thái đơn hàng</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Tổng số: {{ number_format($totalOrders) }} đơn hàng</p>
                </div>
                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-[11px] font-bold text-slate-600">
                    Hủy: {{ $cancelRate }}%
                </span>
            </div>

            <div class="space-y-3.5">
                @forelse($statusBreakdown as $statusKey => $st)
                    <div>
                        <div class="flex items-center justify-between text-xs font-semibold mb-1.5">
                            <span class="flex items-center gap-2 text-slate-700">
                                <span class="w-2.5 h-2.5 rounded-full {{ $st['color'] }}"></span>
                                {{ $st['label'] }}
                            </span>
                            <span class="text-slate-500">
                                <strong class="text-slate-900">{{ number_format($st['count']) }}</strong> đơn 
                                ({{ $st['percentage'] }}%)
                            </span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $st['color'] }} transition-all duration-500" 
                                 style="width: {{ $st['percentage'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-4 text-center">Chưa có đơn hàng nào trong khoảng thời gian này</p>
                @endforelse
            </div>
        </div>

        {{-- Payment Methods Breakdown --}}
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-5">
            <div>
                <h3 class="text-base font-bold text-slate-800 tracking-tight">Phương thức thanh toán</h3>
                <p class="text-xs text-slate-400 mt-0.5">Tỷ trọng thanh toán theo phương thức trong kỳ</p>
            </div>

            <div class="space-y-4">
                @forelse($paymentBreakdown as $pm)
                    <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white shadow-xs border border-slate-100 flex items-center justify-center text-primary">
                                @if($pm['key'] === 'cod')
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">{{ $pm['label'] }}</h4>
                                <p class="text-[11px] text-slate-400">{{ number_format($pm['order_count']) }} đơn hàng ({{ $pm['percentage'] }}%)</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-extrabold text-slate-800">{{ number_format($pm['revenue']) }} đ</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-4 text-center">Chưa có giao dịch nào trong khoảng thời gian này</p>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Grid 2 Columns: Top Selling Products & Low Stock Alert --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Top Selling Products --}}
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 tracking-tight">Top 5 sản phẩm bán chạy nhất</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Xếp hạng theo số lượng bán trong kỳ</p>
                </div>
                <a href="{{ route('admin.products.index') }}" class="text-xs font-bold text-primary hover:underline">
                    Xem tất cả →
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($topProducts as $idx => $prod)
                    <div class="py-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-extrabold {{ $idx === 0 ? 'bg-amber-100 text-amber-700' : ($idx === 1 ? 'bg-slate-200 text-slate-700' : ($idx === 2 ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-500')) }}">
                                {{ $idx + 1 }}
                            </span>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-slate-800 truncate">{{ $prod->product_name }}</h4>
                                <p class="text-[11px] text-slate-400">Đã bán: <strong class="text-emerald-600">{{ number_format($prod->total_qty) }}</strong> cái</p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="text-xs font-bold text-slate-900">{{ number_format($prod->total_revenue) }} đ</span>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-slate-400">
                        Chưa có sản phẩm nào được bán trong giai đoạn này.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Low Stock Warnings --}}
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 tracking-tight">Cảnh báo tồn kho sắp hết</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Biến thể có lượng tồn &le; 10 cần nhập thêm</p>
                </div>
                <a href="{{ route('admin.stock-receipts.create') }}" 
                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary text-white text-[11px] font-bold hover:bg-primary/90 transition-all shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tạo phiếu nhập</span>
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($lowStockVariants as $variant)
                    <div class="py-2.5 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-slate-800 truncate">
                                {{ $variant->product->name ?? 'Sản phẩm' }}
                            </h4>
                            <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                                <span class="font-mono text-slate-500">Phân loại: {{ $variant->variant_title }}</span>
                                <span>&bull;</span>
                                <span>{{ $variant->product->category->name ?? 'Chưa phân loại' }}</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            @if($variant->stock_quantity <= 0)
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    Hết hàng (0)
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    Còn {{ $variant->stock_quantity }} cái
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-emerald-600 font-semibold">
                        Tất cả sản phẩm hiện đang có mức tồn kho dồi dào (&gt; 10 cái).
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Bottom All-time Lifetime Summary --}}
    <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-lg">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-700/60 pb-5">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-rose-300">Toàn cảnh kinh doanh lũy kế</span>
                <h3 class="text-xl font-bold mt-1 text-white">Tổng quan toàn bộ thời gian của Hương Mít</h3>
            </div>
            <a href="{{ route('admin.orders.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs transition-all border border-white/10">
                <span>Quản lý đơn hàng</span>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 pt-5">
            <div>
                <p class="text-xs text-slate-400 font-semibold">Tổng doanh thu lũy kế</p>
                <h4 class="text-xl font-black text-white mt-1">{{ number_format($allTimeStats['total_revenue']) }} đ</h4>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-semibold">Tổng đơn hàng đã tạo</p>
                <h4 class="text-xl font-black text-white mt-1">{{ number_format($allTimeStats['total_orders']) }} đơn</h4>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-semibold">Khách hàng đã đăng ký</p>
                <h4 class="text-xl font-black text-white mt-1">{{ number_format($allTimeStats['total_customers']) }} khách</h4>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-semibold">Sản phẩm trong danh mục</p>
                <h4 class="text-xl font-black text-white mt-1">{{ number_format($allTimeStats['total_products']) }} món</h4>
            </div>
        </div>
    </div>

</div>

{{-- Chart.js Script --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;

    const labels = @json($chartLabels);
    const revenues = @json($chartRevenues);
    const orders = @json($chartOrders);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Doanh thu (VNĐ)',
                    data: revenues,
                    backgroundColor: 'rgba(244, 63, 94, 0.75)',
                    hoverBackgroundColor: 'rgba(244, 63, 94, 0.95)',
                    borderRadius: 8,
                    yAxisID: 'yRevenue',
                    order: 2
                },
                {
                    label: 'Số đơn hàng',
                    data: orders,
                    type: 'line',
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.35,
                    fill: false,
                    yAxisID: 'yOrders',
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { size: 12, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 12,
                    callbacks: {
                        label: function(context) {
                            if (context.dataset.yAxisID === 'yRevenue') {
                                return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' đ';
                            } else {
                                return 'Đơn hàng: ' + context.parsed.y + ' đơn';
                            }
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: { size: 11 }
                    }
                },
                yRevenue: {
                    type: 'linear',
                    position: 'left',
                    grid: {
                        color: 'rgba(226, 232, 240, 0.6)'
                    },
                    ticks: {
                        font: { size: 10 },
                        callback: function(value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return (value / 1000).toFixed(0) + 'k';
                            }
                            return value;
                        }
                    }
                },
                yOrders: {
                    type: 'linear',
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        font: { size: 10 },
                        precision: 0
                    }
                }
            }
        }
    });
});
</script>
@endsection

