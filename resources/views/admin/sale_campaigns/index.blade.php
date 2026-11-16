@extends('layouts.admin')

@section('title', 'Quản lý Chiến dịch Sale - Hương Mít')
@section('page_title', 'Chiến dịch Sale')

@section('content')
<div class="space-y-5" x-data="saleCampaignManager()">

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

        {{-- Status Filter Tabs & Header Action --}}
        <div class="px-6 pt-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 pb-4">
            {{-- Tabs --}}
            <div class="flex items-center gap-2 overflow-x-auto scrollbar-none text-xs font-semibold">
                @php $curStatus = request('status'); @endphp

                <a href="{{ route('admin.sale-campaigns.index', request()->except(['status', 'page'])) }}"
                   class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ empty($curStatus) ? 'bg-primary text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Tất cả ({{ $counts['total'] }})
                </a>

                <a href="{{ route('admin.sale-campaigns.index', array_merge(request()->except(['page']), ['status' => 'running'])) }}"
                   class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $curStatus === 'running' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                    Đang diễn ra ({{ $counts['running'] }})
                </a>

                <a href="{{ route('admin.sale-campaigns.index', array_merge(request()->except(['page']), ['status' => 'upcoming'])) }}"
                   class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $curStatus === 'upcoming' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                    Sắp diễn ra ({{ $counts['upcoming'] }})
                </a>

                <a href="{{ route('admin.sale-campaigns.index', array_merge(request()->except(['page']), ['status' => 'ended'])) }}"
                   class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $curStatus === 'ended' ? 'bg-slate-700 text-white shadow-xs font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Đã kết thúc ({{ $counts['ended'] }})
                </a>

                <a href="{{ route('admin.sale-campaigns.index', array_merge(request()->except(['page']), ['status' => 'paused'])) }}"
                   class="px-4 py-2 rounded-xl transition-all whitespace-nowrap {{ $curStatus === 'paused' ? 'bg-rose-600 text-white shadow-xs font-bold' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                    Tạm dừng ({{ $counts['paused'] }})
                </a>
            </div>

            {{-- Create Button --}}
            <a href="{{ route('admin.sale-campaigns.create') }}"
               class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-primary to-rose-500 hover:from-primary-600 hover:to-rose-600 text-white text-xs font-bold shadow-md shadow-primary/20 flex items-center gap-2 transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Tạo chiến dịch sale mới</span>
            </a>
        </div>

        {{-- Search and Filter Form --}}
        <div class="px-6 pb-2">
            <form method="GET" action="{{ route('admin.sale-campaigns.index') }}" class="flex flex-wrap items-center gap-3">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
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
                           placeholder="Tìm theo tên chiến dịch hoặc mô tả..." 
                           class="w-full pl-10 pr-4 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                </div>

                <!-- Sort Filter -->
                <div class="min-w-[170px]">
                    <select name="sort" onchange="this.form.submit()" 
                            class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-200 text-xs text-slate-700 bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none cursor-pointer">
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Mới nhất trước</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Cũ nhất trước</option>
                        <option value="start_asc" {{ request('sort') == 'start_asc' ? 'selected' : '' }}>Sắp bắt đầu</option>
                        <option value="start_desc" {{ request('sort') == 'start_desc' ? 'selected' : '' }}>Bắt đầu xa nhất</option>
                    </select>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="px-5 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold shadow-xs transition-all">
                    Lọc dữ liệu
                </button>

                @if(request()->hasAny(['search', 'sort', 'status']))
                    <a href="{{ route('admin.sale-campaigns.index') }}" 
                       class="px-4 py-2.5 rounded-2xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition-all">
                        Đặt lại
                    </a>
                @endif
            </form>
        </div>

        {{-- Table Campaigns List --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/70 border-y border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3.5 px-6">Tên chiến dịch</th>
                        <th class="py-3.5 px-4">Thời gian áp dụng</th>
                        <th class="py-3.5 px-4 text-center">Số sản phẩm</th>
                        <th class="py-3.5 px-4 text-center">Tiến độ bán (Suất)</th>
                        <th class="py-3.5 px-4 text-center">Trạng thái</th>
                        <th class="py-3.5 px-4 text-center">Bật / Tắt</th>
                        <th class="py-3.5 px-6 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($campaigns as $camp)
                        @php
                            $totalSaleQty = $camp->items->sum('sale_quantity');
                            $totalSoldQty = $camp->items->sum('sold_quantity');
                            $progressPct = $totalSaleQty > 0 ? min(100, round(($totalSoldQty / $totalSaleQty) * 100)) : 0;
                            $isEnded = $camp->isEnded();
                            $hasStarted = $camp->hasStartedOrRunning();
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            {{-- Campaign Name & Description --}}
                            <td class="py-4 px-6">
                                <div class="font-bold text-slate-900 text-sm mb-0.5">
                                    {{ $camp->name }}
                                </div>
                                @if($camp->description)
                                    <p class="text-[11px] text-slate-400 line-clamp-1 max-w-xs">{{ $camp->description }}</p>
                                @endif
                            </td>

                            {{-- Time Schedule --}}
                            <td class="py-4 px-4 whitespace-nowrap">
                                <div class="text-xs font-semibold text-slate-800">{{ $camp->start_time->format('d/m/Y H:i') }}</div>
                                <div class="text-[11px] text-slate-400">đến {{ $camp->end_time->format('d/m/Y H:i') }}</div>
                            </td>

                            {{-- Products Count --}}
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-xl text-xs font-bold bg-slate-100 text-slate-700">
                                    {{ $camp->items->count() }} mặt hàng
                                </span>
                            </td>

                            {{-- Sale Progress --}}
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <div class="w-36 mx-auto space-y-1">
                                    <div class="flex justify-between text-[11px] font-bold">
                                        <span class="text-primary">{{ $totalSoldQty }} đã bán</span>
                                        <span class="text-slate-400">/ {{ $totalSaleQty }} suất</span>
                                    </div>
                                    <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-primary to-rose-500 rounded-full transition-all" style="width: {{ $progressPct }}%"></div>
                                    </div>
                                </div>
                            </td>

                            {{-- Status Badge --}}
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                @if($camp->status === 'running')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Đang diễn ra
                                    </span>
                                @elseif($camp->status === 'upcoming')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        Sắp diễn ra
                                    </span>
                                @elseif($camp->status === 'ended')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        Đã kết thúc
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Đã tạm dừng
                                    </span>
                                @endif
                            </td>

                            {{-- Toggle Active Switch --}}
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                @if($isEnded)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed select-none" title="Chiến dịch đã kết thúc (khóa tắt)">
                                        Khóa tắt
                                    </span>
                                @else
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" 
                                               {{ $camp->is_active ? 'checked' : '' }} 
                                               @change="toggleCampaign({{ $camp->id }}, $event.target)"
                                               class="sr-only peer">
                                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if($isEnded)
                                        <!-- View Detail Only Button -->
                                        <a href="{{ route('admin.sale-campaigns.edit', $camp) }}"
                                           title="Xem chi tiết (Chiến dịch đã kết thúc)"
                                           class="p-2 rounded-xl text-slate-400 hover:text-primary hover:bg-primary-50 transition-all">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                    @else
                                        <!-- Edit Button -->
                                        <a href="{{ route('admin.sale-campaigns.edit', $camp) }}"
                                           title="Chỉnh sửa chiến dịch"
                                           class="p-2 rounded-xl text-slate-400 hover:text-primary hover:bg-primary-50 transition-all">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    @endif

                                    <!-- Delete Button -->
                                    @if($hasStarted)
                                        <span title="Không thể xóa chiến dịch đã hoặc đang diễn ra" 
                                              class="p-2 rounded-xl text-slate-200 cursor-not-allowed inline-block">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('admin.sale-campaigns.destroy', $camp) }}"
                                              onsubmit="return confirm('Bạn có chắc chắn muốn xóa chiến dịch sale [{{ $camp->name }}] không?');"
                                              class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    title="Xóa chiến dịch"
                                                    class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="font-bold text-slate-700 text-sm mb-1">Chưa có chiến dịch sale nào</div>
                                <p class="text-xs text-slate-400 max-w-sm mx-auto mb-4">Hãy tạo chiến dịch sale đầu tiên để kích cầu mua sắm và áp dụng mức giá ưu đãi trong khung giờ vàng!</p>
                                <a href="{{ route('admin.sale-campaigns.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary text-white text-xs font-bold hover:bg-primary-600 transition-all">
                                    + Tạo chiến dịch ngay
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($campaigns->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function saleCampaignManager() {
    return {
        async toggleCampaign(campaignId, checkbox) {
            try {
                const res = await fetch(`/admin/sale-campaigns/${campaignId}/toggle-status`, {
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
                } else {
                    window.location.reload();
                }
            } catch (e) {
                alert('Lỗi cập nhật: ' + e.message);
                checkbox.checked = !checkbox.checked;
            }
        }
    };
}
</script>
@endpush
