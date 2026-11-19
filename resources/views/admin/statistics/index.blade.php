@extends('layouts.admin')

@section('title', 'Thống kê Doanh thu - Hương Mít')
@section('page_title', 'Thống kê Doanh thu')

@section('content')
<div class="space-y-6" x-data="{ customDateOpen: {{ $period === 'custom' ? 'true' : 'false' }} }">

    {{-- Top Filter Bar: Bộ lọc thời gian tinh gọn --}}
    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-100 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-2xl bg-primary/10 text-primary">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight">Báo cáo doanh thu bán hàng</h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Kỳ báo cáo: <span class="font-bold text-primary">{{ $periodLabel }}</span>
                        </p>
                    </div>
                </div>
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
             class="mt-4 pt-4 border-t border-slate-100">
            <form method="GET" action="{{ route('admin.statistics.index') }}" class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="period" value="custom">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Từ ngày</label>
                    <input type="date" name="from_date" 
                           value="{{ request('from_date', $startDate->format('Y-m-d')) }}"
                           class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Đến ngày</label>
                    <input type="date" name="to_date" 
                           value="{{ request('to_date', $endDate->format('Y-m-d')) }}"
                           class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <button type="submit" 
                        class="px-5 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary/90 transition-all shadow-sm shadow-primary/30 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span>Áp dụng lọc</span>
                </button>
            </form>
        </div>
    </div>

    {{-- 4 Main KPI Cards: 4 Chỉ số Doanh thu then chốt --}}
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
                    <span>Doanh thu / đơn thành công</span>
                </div>
            </div>
        </div>

        <!-- Sản phẩm đã bán -->
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
                    <span>{{ number_format($newCustomersCount) }} khách hàng mới</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Chart: Biểu đồ doanh thu & đơn hàng theo thời gian --}}
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-800 tracking-tight">Biểu đồ doanh thu & số lượng đơn hàng</h3>
                <p class="text-xs text-slate-400 mt-0.5">Biểu diễn tăng trưởng kinh doanh trong kỳ</p>
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

    {{-- 2 Khối Phân tích Doanh thu Trọng tâm: Top Sản Phẩm & Nguồn Tiền Thanh Toán --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Khối 1: Top 5 sản phẩm bán chạy nhất --}}
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 tracking-tight">Top 5 sản phẩm bán chạy nhất</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Sản phẩm mang lại sản lượng & doanh thu lớn nhất</p>
                </div>
                <a href="{{ route('admin.products.index') }}" class="text-xs font-bold text-primary hover:underline flex items-center gap-1">
                    <span>Xem sản phẩm</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($topProducts as $idx => $prod)
                    <div class="py-3.5 flex items-center justify-between gap-3 group">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-7 h-7 rounded-xl flex items-center justify-center text-xs font-black {{ $idx === 0 ? 'bg-amber-100 text-amber-700 shadow-xs' : ($idx === 1 ? 'bg-slate-200 text-slate-700' : ($idx === 2 ? 'bg-orange-100 text-orange-800' : 'bg-slate-100 text-slate-500')) }}">
                                {{ $idx + 1 }}
                            </span>
                            <div class="min-w-0">
                                <h4 class="text-xs font-bold text-slate-800 truncate group-hover:text-primary transition-colors">
                                    {{ $prod->product_name }}
                                </h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Đã bán: <strong class="text-emerald-600 font-bold">{{ number_format($prod->total_qty) }}</strong> cái
                                </p>
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <span class="text-xs font-extrabold text-slate-900 block">{{ number_format($prod->total_revenue) }} đ</span>
                            <span class="text-[10px] text-slate-400 font-medium">Doanh số</span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <p class="text-xs text-slate-400 font-medium">Chưa có sản phẩm nào phát sinh doanh thu trong kỳ</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Khối 2: Doanh thu theo Phương thức thanh toán --}}
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-800 tracking-tight">Doanh thu theo phương thức thanh toán</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Nguồn tiền thu qua COD vs Chuyển khoản VietQR</p>
                </div>
                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-[11px] font-bold text-slate-600">
                    {{ count($paymentBreakdown) }} phương thức
                </span>
            </div>

            <div class="space-y-3 pt-1">
                @forelse($paymentBreakdown as $pm)
                    <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-100 hover:border-slate-200 transition-all">
                        <div class="flex items-center justify-between gap-3 mb-2.5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl {{ $pm['key'] === 'bank_transfer' ? 'bg-indigo-50 text-indigo-600' : 'bg-emerald-50 text-emerald-600' }} flex items-center justify-center flex-shrink-0">
                                    @if($pm['key'] === 'bank_transfer')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800">{{ $pm['label'] }}</h4>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        <strong class="text-slate-700">{{ number_format($pm['order_count']) }}</strong> đơn hàng
                                        ({{ $pm['percentage'] }}%)
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-black text-slate-900 block">{{ number_format($pm['revenue']) }} đ</span>
                                <span class="text-[10px] font-bold {{ $pm['key'] === 'bank_transfer' ? 'text-indigo-600' : 'text-emerald-600' }}">
                                    {{ $revenue > 0 ? round(($pm['revenue'] / $revenue) * 100, 1) : 0 }}% doanh thu
                                </span>
                            </div>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-slate-200/70 overflow-hidden">
                            <div class="h-full rounded-full {{ $pm['key'] === 'bank_transfer' ? 'bg-indigo-500' : 'bg-emerald-500' }}"
                                 style="width: {{ $revenue > 0 ? round(($pm['revenue'] / $revenue) * 100, 1) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-xs text-slate-400">
                        Chưa có giao dịch thanh toán nào trong kỳ
                    </div>
                @endforelse
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
