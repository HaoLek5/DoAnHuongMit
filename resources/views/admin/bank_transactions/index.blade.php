@extends('layouts.admin')

@section('title', 'Đối soát Chuyển khoản - Hương Mít Admin')
@section('page_title', 'Đối soát Chuyển khoản Ngân hàng')

@section('content')
<div class="space-y-6">

    <!-- Header Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm">
            <div class="text-xs font-semibold text-slate-400">Tổng giao dịch thực nhận đã ghi sổ</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $counts['all'] }}</div>
        </div>
        <div class="bg-white rounded-3xl p-5 border {{ $counts['needs_review'] > 0 ? 'border-rose-200 bg-rose-50/40' : 'border-slate-100' }} shadow-sm">
            <div class="text-xs font-semibold {{ $counts['needs_review'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">Giao dịch cần đối soát thủ công</div>
            <div class="text-2xl font-black {{ $counts['needs_review'] > 0 ? 'text-rose-700' : 'text-slate-900' }} mt-1">
                {{ $counts['needs_review'] }}
                @if($needsReviewAmount > 0)
                    <span class="text-xs font-bold ml-1">({{ number_format($needsReviewAmount, 0, ',', '.') }} đ)</span>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-3xl p-5 border border-slate-100 shadow-sm">
            <div class="text-xs font-semibold text-slate-400">Đã khớp tự động / Đã đối soát</div>
            <div class="text-2xl font-black text-emerald-700 mt-1">{{ $counts['auto_matched'] + $counts['resolved'] }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs font-semibold text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filter & Search -->
    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 pt-5 border-b border-slate-100 flex items-center gap-2 overflow-x-auto pb-4 text-xs font-semibold">
            <a href="{{ route('admin.bank-transactions.index', ['reconciliation' => 'all']) }}"
               class="px-4 py-2 rounded-xl whitespace-nowrap {{ $reconciliation === 'all' ? 'bg-primary text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Tất cả ({{ $counts['all'] }})
            </a>
            <a href="{{ route('admin.bank-transactions.index', ['reconciliation' => 'needs_review']) }}"
               class="px-4 py-2 rounded-xl whitespace-nowrap {{ $reconciliation === 'needs_review' ? 'bg-rose-600 text-white font-bold' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                Cần đối soát ({{ $counts['needs_review'] }})
            </a>
            <a href="{{ route('admin.bank-transactions.index', ['reconciliation' => 'auto_matched']) }}"
               class="px-4 py-2 rounded-xl whitespace-nowrap {{ $reconciliation === 'auto_matched' ? 'bg-emerald-600 text-white font-bold' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                Khớp tự động ({{ $counts['auto_matched'] }})
            </a>
            <a href="{{ route('admin.bank-transactions.index', ['reconciliation' => 'resolved']) }}"
               class="px-4 py-2 rounded-xl whitespace-nowrap {{ $reconciliation === 'resolved' ? 'bg-blue-600 text-white font-bold' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                Đã xử lý ({{ $counts['resolved'] }})
            </a>
        </div>

        <div class="p-6 pb-2">
            <form method="GET" action="{{ route('admin.bank-transactions.index') }}" class="flex flex-wrap items-center gap-3">
                <input type="hidden" name="reconciliation" value="{{ $reconciliation }}">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Tìm mã HM..., mã GD ngân hàng, mã đơn DH..."
                       class="flex-1 min-w-[240px] px-4 py-2.5 rounded-2xl border border-slate-200 text-xs focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                <select name="status" class="px-4 py-2.5 rounded-2xl border border-slate-200 text-xs bg-white">
                    <option value="">Tất cả loại chênh lệch</option>
                    <option value="underpaid" {{ $statusFilter === 'underpaid' ? 'selected' : '' }}>Chuyển thiếu tiền</option>
                    <option value="overpaid" {{ $statusFilter === 'overpaid' ? 'selected' : '' }}>Chuyển thừa tiền</option>
                    <option value="expired_late" {{ $statusFilter === 'expired_late' ? 'selected' : '' }}>Chuyển sau hết hạn QR</option>
                    <option value="session_cancelled" {{ $statusFilter === 'session_cancelled' ? 'selected' : '' }}>Phiên QR đã hủy</option>
                    <option value="unmatched_code" {{ $statusFilter === 'unmatched_code' ? 'selected' : '' }}>Không khớp mã HM</option>
                    <option value="duplicate_tx" {{ $statusFilter === 'duplicate_tx' ? 'selected' : '' }}>Chuyển trùng lần 2</option>
                    <option value="out_of_stock" {{ $statusFilter === 'out_of_stock' ? 'selected' : '' }}>Hết tồn kho khi tiền về</option>
                    <option value="matched" {{ $statusFilter === 'matched' ? 'selected' : '' }}>Khớp đơn</option>
                </select>
                <button type="submit" class="px-5 py-2.5 rounded-2xl bg-slate-900 text-white text-xs font-semibold">Lọc</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-y border-slate-100 text-[11px] uppercase text-slate-400 font-bold">
                    <tr>
                        <th class="py-3.5 px-5">Mã GD / Thời gian</th>
                        <th class="py-3.5 px-4">Nội dung / Phiên HM</th>
                        <th class="py-3.5 px-4 text-right">Thực nhận / Yêu cầu</th>
                        <th class="py-3.5 px-4">Trạng thái tiền</th>
                        <th class="py-3.5 px-4">Đơn hàng & Ghi chú</th>
                        <th class="py-3.5 px-5 text-right">Đối soát</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50/70 align-top">
                            <td class="py-4 px-5">
                                <div class="font-mono font-bold text-slate-800">{{ $tx->transaction_code ?? ($tx->sepay_id ? '#'.$tx->sepay_id : 'N/A') }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ ($tx->occurred_at ?? $tx->created_at)?->format('d/m/Y H:i:s') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $tx->gateway ?? 'SePay' }} {{ $tx->account_number_last4 ? '•••'.$tx->account_number_last4 : '' }}</div>
                            </td>
                            <td class="py-4 px-4 max-w-xs">
                                @if($tx->transfer_code)
                                    <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 font-mono font-bold text-slate-800 text-[11px]">{{ $tx->transfer_code }}</span>
                                @endif
                                <div class="text-[11px] text-slate-600 mt-1 break-words">{{ $tx->raw_content ?? '—' }}</div>
                                @if($tx->session?->user)
                                    <div class="text-[10px] text-slate-400 mt-1">Khách: {{ $tx->session->user->name }} ({{ $tx->session->user->phone ?? $tx->session->user->email }})</div>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-right font-mono">
                                <div class="font-black text-slate-900">{{ number_format($tx->received_amount, 0, ',', '.') }} đ</div>
                                @if($tx->expected_amount !== null)
                                    <div class="text-[11px] text-slate-400">Yêu cầu: {{ number_format($tx->expected_amount, 0, ',', '.') }} đ</div>
                                @endif
                                @if((float) $tx->difference_amount !== 0.0)
                                    <div class="text-[11px] font-bold {{ (float) $tx->difference_amount > 0 ? 'text-amber-700' : 'text-rose-600' }}">
                                        {{ (float) $tx->difference_amount > 0 ? '+' : '' }}{{ number_format($tx->difference_amount, 0, ',', '.') }} đ
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $tx->status_badge_class }}">
                                    {{ $tx->status_label }}
                                </span>
                                <div class="text-[11px] mt-1 font-semibold {{ $tx->reconciliation_status === 'needs_review' ? 'text-rose-600' : 'text-slate-500' }}">
                                    {{ $tx->reconciliation_label }}
                                </div>
                            </td>
                            <td class="py-4 px-4 max-w-xs">
                                @if($tx->order)
                                    <a href="{{ route('admin.orders.show', $tx->order) }}" class="font-mono font-bold text-primary hover:underline">
                                        #{{ $tx->order->order_code }}
                                    </a>
                                @endif
                                @if($tx->note)
                                    <div class="text-[11px] text-slate-600 whitespace-pre-line mt-1">{{ $tx->note }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-5 text-right">
                                @if($tx->reconciliation_status === 'needs_review')
                                    <form action="{{ route('admin.bank-transactions.reconcile', $tx) }}" method="POST" class="space-y-1.5 text-left min-w-[200px]">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="reconciliation_status" value="resolved">
                                        @if(!$tx->order)
                                            <input type="text" name="order_code" placeholder="Gắn mã đơn DH... (nếu có)" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-[11px]">
                                        @endif
                                        <input type="text" name="note" placeholder="Ghi chú xử lý / hoàn tiền..." class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-[11px]">
                                        <button type="submit" class="w-full px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px]">
                                            Đánh dấu đã đối soát
                                        </button>
                                    </form>
                                @elseif($tx->reconciled_at)
                                    <div class="text-[11px] text-emerald-700 font-semibold">
                                        ✓ {{ $tx->reconciler?->name ?? 'Tự động' }}
                                        <div class="text-[10px] text-slate-400">{{ $tx->reconciled_at->format('d/m H:i') }}</div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 text-xs">
                                Chưa có giao dịch chuyển khoản nào được ghi nhận.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-5 border-t border-slate-100">
            {{ $transactions->links() }}
        </div>
    </div>

</div>
@endsection

