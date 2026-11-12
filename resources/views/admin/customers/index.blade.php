@extends('layouts.admin')

@section('title', 'Quản lý Tài khoản khách hàng - Hương Mít')
@section('page_title', 'Tài khoản khách hàng')

@section('content')
<div class="space-y-6">

    {{-- Alert Notifications --}}
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800 flex items-center gap-2.5 shadow-xs">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800 flex items-center gap-2.5 shadow-xs">
            <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Main Container Card --}}
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden space-y-4">

        {{-- Status Filter Tabs --}}
        <div class="px-6 pt-5 border-b border-slate-100 flex items-center gap-2 overflow-x-auto pb-4 scrollbar-none text-xs font-semibold">
            @php 
                $curStatus = request('status'); 
                $curOrders = request('orders');
            @endphp

            <a href="{{ route('admin.customers.index', request()->except(['status', 'orders', 'page'])) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ empty($curStatus) && empty($curOrders) ? 'bg-primary text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Tất cả ({{ $counts['total'] }})
            </a>

            <a href="{{ route('admin.customers.index', array_merge(request()->except(['page', 'orders']), ['status' => 'active'])) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $curStatus === 'active' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                Đang hoạt động ({{ $counts['total'] - $counts['locked'] }})
            </a>

            <a href="{{ route('admin.customers.index', array_merge(request()->except(['page', 'orders']), ['status' => 'locked'])) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $curStatus === 'locked' ? 'bg-amber-600 text-white shadow-xs font-bold' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                Tạm khóa ({{ $counts['locked'] }})
            </a>

            <a href="{{ route('admin.customers.index', array_merge(request()->except(['page', 'status']), ['orders' => 'has_orders'])) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $curOrders === 'has_orders' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Đã mua hàng ({{ $counts['has_orders'] }})
            </a>

            <a href="{{ route('admin.customers.index', array_merge(request()->except(['page', 'status']), ['orders' => 'no_orders'])) }}"
               class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $curOrders === 'no_orders' ? 'bg-slate-700 text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Chưa mua hàng ({{ $counts['total'] - $counts['has_orders'] }})
            </a>
        </div>

        {{-- Search and Filter Form --}}
        <div class="px-6 pb-2">
            <form method="GET" action="{{ route('admin.customers.index') }}" class="flex flex-wrap items-center gap-3">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('orders'))
                    <input type="hidden" name="orders" value="{{ request('orders') }}">
                @endif

                <!-- Search Input -->
                <div class="relative flex-1 min-w-[240px]">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Tìm kiếm theo Tên, Email hoặc Số điện thoại..." 
                           class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                </div>

                <!-- Sort Filter -->
                <div class="min-w-[170px]">
                    <select name="sort" onchange="this.form.submit()" 
                            class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-700 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none cursor-pointer">
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Mới nhất trước</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất trước</option>
                        <option value="most_orders" {{ request('sort') == 'most_orders' ? 'selected' : '' }}>Đơn nhiều nhất</option>
                        <option value="most_spent" {{ request('sort') == 'most_spent' ? 'selected' : '' }}>Chi tiêu nhiều nhất</option>
                        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>Tên A - Z</option>
                    </select>
                </div>

                <!-- Submit & Reset Buttons -->
                <button type="submit" 
                        class="px-4 py-2.5 rounded-2xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition-colors flex items-center gap-1.5 shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span>Lọc dữ liệu</span>
                </button>

                @if(request()->hasAny(['search', 'status', 'orders', 'sort']))
                    <a href="{{ route('admin.customers.index') }}" 
                       class="px-3.5 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs transition-colors">
                        Đặt lại
                    </a>
                @endif
            </form>
        </div>

        {{-- Customers Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-y border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">Khách hàng</th>
                        <th class="py-3.5 px-4">Thông tin liên hệ</th>
                        <th class="py-3.5 px-4 text-center">Số đơn hàng</th>
                        <th class="py-3.5 px-4 text-right">Tổng chi tiêu</th>
                        <th class="py-3.5 px-4 text-center">Trạng thái</th>
                        <th class="py-3.5 px-4">Ngày đăng ký</th>
                        <th class="py-3.5 px-6 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($customers as $customer)
                        @php
                            $firstLetter = mb_substr($customer->name, 0, 1, 'UTF-8');
                            $avatarColors = [
                                'bg-rose-100 text-rose-700',
                                'bg-blue-100 text-blue-700',
                                'bg-emerald-100 text-emerald-700',
                                'bg-amber-100 text-amber-700',
                                'bg-purple-100 text-purple-700',
                                'bg-indigo-100 text-indigo-700',
                            ];
                            $colorClass = $avatarColors[$customer->id % count($avatarColors)];
                            $isLocked = $customer->isLocked();
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors group">
                            
                            <!-- Customer Info & Avatar -->
                            <td class="py-3.5 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl {{ $colorClass }} font-bold text-sm flex items-center justify-center flex-shrink-0 shadow-xs uppercase">
                                        {{ $firstLetter }}
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.customers.show', $customer) }}" 
                                           class="font-bold text-slate-900 group-hover:text-primary transition-colors block truncate hover:underline">
                                            {{ $customer->name }}
                                        </a>
                                        <span class="text-[11px] font-mono text-slate-400">ID: #{{ $customer->id }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Contact info: Email & Phone -->
                            <td class="py-3.5 px-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-1.5 text-slate-800">
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="truncate max-w-[200px]">{{ $customer->email }}</span>
                                    </div>
                                    @if($customer->phone)
                                        <div class="flex items-center gap-1.5 text-slate-500 font-mono text-[11px]">
                                            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                            <span>{{ $customer->phone }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Total Orders -->
                            <td class="py-3.5 px-4 text-center">
                                @if($customer->orders_count > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 font-bold text-xs">
                                        <span>{{ $customer->orders_count }}</span>
                                        <span class="text-[10px] font-medium text-blue-600">đơn</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[11px] italic">Chưa có đơn</span>
                                @endif
                            </td>

                            <!-- Total Spent -->
                            <td class="py-3.5 px-4 text-right">
                                <span class="font-extrabold text-slate-900 font-mono">
                                    {{ number_format($customer->total_spent ?? 0, 0, ',', '.') }} đ
                                </span>
                            </td>

                            <!-- Account Status (Active vs Locked) -->
                            <td class="py-3.5 px-4 text-center">
                                @if($isLocked)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-rose-50 text-rose-700 font-bold text-[11px] border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        <span>Tạm khóa</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 font-bold text-[11px] border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        <span>Hoạt động</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Registered Date -->
                            <td class="py-3.5 px-4">
                                <div class="text-slate-800 font-medium">
                                    {{ $customer->created_at ? $customer->created_at->format('d/m/Y') : '—' }}
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $customer->created_at ? $customer->created_at->diffForHumans() : '' }}
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-6 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- View Details -->
                                    <a href="{{ route('admin.customers.show', $customer) }}" 
                                       title="Xem chi tiết tài khoản"
                                       class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-primary hover:text-white text-slate-600 flex items-center justify-center transition-all shadow-xs">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-7 4a7 7 0 1114 0H8z"/>
                                        </svg>
                                    </a>

                                    <!-- Toggle Lock/Unlock -->
                                    <form method="POST" action="{{ route('admin.customers.toggle-lock', $customer) }}"
                                          onsubmit="return confirm('Bạn có chắc chắn muốn {{ $isLocked ? 'mở khóa' : 'khóa' }} tài khoản khách hàng {{ $customer->name }}?');">
                                        @csrf
                                        <button type="submit" 
                                                title="{{ $isLocked ? 'Mở khóa tài khoản' : 'Khóa tài khoản' }}"
                                                class="w-8 h-8 rounded-xl {{ $isLocked ? 'bg-amber-100 hover:bg-emerald-600 text-amber-700 hover:text-white' : 'bg-slate-100 hover:bg-rose-600 text-slate-600 hover:text-white' }} flex items-center justify-center transition-all shadow-xs cursor-pointer">
                                            @if($isLocked)
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center">
                                <div class="max-w-xs mx-auto text-slate-400 space-y-2">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto text-slate-400">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                    </div>
                                    <p class="font-bold text-slate-700 text-sm">Không tìm thấy tài khoản khách hàng nào</p>
                                    <p class="text-xs text-slate-400">Thử thay đổi từ khóa tìm kiếm hoặc bỏ các tiêu chí lọc dữ liệu.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $customers->links() }}
            </div>
        @endif

    </div>

</div>
@endsection

