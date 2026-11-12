@php
    $printOrders = isset($orders) && $orders->isNotEmpty() ? $orders : (isset($order) ? collect([$order]) : collect());
    $pageTitle = $printOrders->count() > 1 
        ? 'In ' . $printOrders->count() . ' Phiếu Giao Hàng - Hương Mít'
        : 'Phiếu Giao Hàng #' . ($printOrders->first()?->order_code ?? '') . ' - Hương Mít';
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            padding: 24px;
            font-size: 13px;
            line-height: 1.5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px dashed #cbd5e1;
            padding: 32px;
            border-radius: 12px;
            background: #fff;
        }
        @media screen {
            .order-sheet {
                margin-bottom: 32px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            }
        }
        @media print {
            body { padding: 0; background: #fff; }
            .order-sheet { 
                border: none !important; 
                padding: 16px 20px !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                page-break-after: always; 
                break-after: page;
            }
            .order-sheet:last-child {
                page-break-after: auto;
                break-after: auto;
            }
            .print-btn { display: none !important; }
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .shop-info h1 {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .shop-info p {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .order-title {
            text-align: right;
        }
        .order-title h2 {
            font-size: 18px;
            font-weight: 800;
            color: #e11d48;
        }
        .order-title p {
            font-size: 12px;
            color: #64748b;
            font-family: monospace;
            margin-top: 2px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
        }
        .info-block h3 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 6px;
            font-weight: 700;
        }
        .info-block p {
            font-size: 13px;
            color: #1e293b;
        }
        .info-block strong {
            font-size: 14px;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        th {
            background: #f1f5f9;
            color: #475569;
            text-align: left;
            padding: 10px 12px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            border-bottom: 1px solid #cbd5e1;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .totals {
            margin-left: auto;
            width: 320px;
            margin-bottom: 24px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 12px;
            color: #475569;
        }
        .totals-row.grand-total {
            border-top: 2px solid #0f172a;
            padding-top: 8px;
            margin-top: 6px;
            font-size: 16px;
            font-weight: 800;
            color: #e11d48;
        }
        .cod-notice {
            background: #fef2f2;
            border: 1px solid #fecdd3;
            padding: 12px 16px;
            border-radius: 8px;
            color: #9f1239;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
        }
        .sig-block h4 {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
        }
        .sig-block p {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 2px;
        }
        .sig-space {
            height: 70px;
        }
        .print-btn {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #e11d48;
            color: #fff;
            padding: 12px 24px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(225, 29, 72, 0.4);
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-btn">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <span>In {{ $printOrders->count() }} phiếu giao hàng</span>
    </button>

    @forelse($printOrders as $order)
    <div class="container order-sheet">
        <!-- Header -->
        <div class="header">
            <div class="shop-info">
                <h1>HƯƠNG MÍT STORE</h1>
                <p>Hệ thống phân phối Sữa & Đồ dùng Mẹ và Bé cao cấp</p>
                <p>Hotline: 0900.000.001 - Website: haomilk.vn</p>
            </div>
            <div class="order-title">
                <h2>PHIẾU GIAO HÀNG</h2>
                <p class="font-mono">#{{ $order->order_code }}</p>
                <p style="font-size: 11px; margin-top: 4px;">Ngày: {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <!-- Recipient & Sender Info -->
        <div class="info-grid">
            <div class="info-block">
                <h3>Người gửi:</h3>
                <strong>HƯƠNG MÍT CHÍNH HÃNG</strong>
                <p>Hotline hỗ trợ: 0900.000.001</p>
                <p>Hà Nội, Việt Nam</p>
            </div>
            <div class="info-block">
                <h3>Người nhận hàng:</h3>
                <strong>{{ $order->receiver_name }}</strong>
                <p>Số điện thoại: <strong>{{ $order->receiver_phone }}</strong></p>
                <p>{{ $order->shipping_address }}</p>
            </div>
        </div>

        <!-- Ordered Items Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">STT</th>
                    <th>Tên sản phẩm & Phân loại</th>
                    <th class="text-center" style="width: 100px;">Mã SKU</th>
                    <th class="text-center" style="width: 70px;">SL</th>
                    <th class="text-right" style="width: 110px;">Đơn giá</th>
                    <th class="text-right" style="width: 120px;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $idx => $item)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $item->product_name }}</strong>
                            @if($item->variant_name && $item->variant_name !== 'Mặc định')
                                <span style="color: #e11d48; font-size: 11px; display: block;">Phân loại: {{ $item->variant_name }}</span>
                            @endif
                        </td>
                        <td class="text-center font-mono" style="font-size: 11px; color: #64748b;">{{ $item->sku }}</td>
                        <td class="text-center font-mono"><strong>{{ $item->quantity }}</strong></td>
                        <td class="text-right font-mono">{{ number_format($item->price, 0, ',', '.') }} đ</td>
                        <td class="text-right font-mono"><strong>{{ number_format($item->subtotal, 0, ',', '.') }} đ</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals & Payment Summary -->
        <div class="totals">
            <div class="totals-row">
                <span>Tạm tính tiền hàng:</span>
                <span class="font-mono">{{ number_format($order->subtotal, 0, ',', '.') }} đ</span>
            </div>
            <div class="totals-row">
                <span>Phí vận chuyển:</span>
                <span>Thanh toán khi nhận</span>
            </div>
            @if($order->discount_amount > 0)
            <div class="totals-row">
                <span>Giảm giá:</span>
                <span class="font-mono">-{{ number_format($order->discount_amount, 0, ',', '.') }} đ</span>
            </div>
            @endif
            @if($order->deposit_required && (float) $order->deposit_amount > 0 && (float) $order->paid_amount > 0)
                <div class="totals-row">
                    <span>Tổng giá trị đơn hàng:</span>
                    <span class="font-mono">{{ number_format($order->total_amount, 0, ',', '.') }} đ</span>
                </div>
                <div class="totals-row" style="color: #059669; font-weight: 600;">
                    <span>Đã đặt cọc (VietQR 40%):</span>
                    <span class="font-mono">-{{ number_format($order->deposit_amount, 0, ',', '.') }} đ</span>
                </div>
                <div class="totals-row grand-total" style="color: {{ $order->payment_status === 'paid' ? '#059669' : '#b45309' }};">
                    <span>{{ $order->payment_status === 'paid' ? 'TIỀN PHẢI THU SHIPPER (ĐÃ TẤT TOÁN):' : 'TIỀN PHẢI THU SHIPPER (60% COD):' }}</span>
                    <span class="font-mono">{{ number_format($order->remaining_amount, 0, ',', '.') }} đ</span>
                </div>
            @elseif($order->payment_method === 'bank_transfer' && $order->payment_status === 'paid')
                <div class="totals-row">
                    <span>Đã thanh toán (VietQR):</span>
                    <span class="font-mono">{{ number_format($order->paid_amount, 0, ',', '.') }} đ</span>
                </div>
                <div class="totals-row grand-total" style="color: #059669;">
                    <span>TIỀN PHẢI THU SHIPPER:</span>
                    <span class="font-mono">0 đ</span>
                </div>
            @else
                <div class="totals-row grand-total">
                    <span>TỔNG TIỀN PHẢI THU (COD):</span>
                    <span class="font-mono">{{ number_format($order->total_amount, 0, ',', '.') }} đ</span>
                </div>
            @endif
        </div>

        <!-- Payment Notice Banner -->
        @if($order->deposit_required && (float) $order->deposit_amount > 0 && (float) $order->paid_amount > 0)
            @if($order->payment_status === 'paid' && (float) $order->remaining_amount <= 0)
                <div class="cod-notice" style="background: #ecfdf5; border: 1.5px solid #6ee7b7; color: #065f46;">
                    <div>
                        <strong style="color: #047857; font-size: 13px;">HÌNH THỨC: COD ĐÃ ĐẶT CỌC 40% ({{ number_format($order->deposit_amount, 0, ',', '.') }} đ) & ĐÃ TẤT TOÁN PHẦN CÒN LẠI</strong>
                        <p style="font-size: 11px; margin-top: 2px; color: #047857; font-weight: 600;">⚠️ ĐƠN HÀNG ĐÃ THANH TOÁN ĐỦ — KHÔNG THU THÊM TIỀN HÀNG.</p>
                    </div>
                    <div style="font-size: 16px; font-weight: 900; color: #059669;">
                        0 đ
                    </div>
                </div>
            @else
                <div class="cod-notice" style="background: #fffbeb; border: 2px solid #f59e0b; color: #78350f;">
                    <div>
                        <strong style="color: #b45309; font-size: 13px;">HÌNH THỨC: COD ĐÃ ĐẶT CỌC 40% (CHUYỂN KHOẢN {{ number_format($order->deposit_amount, 0, ',', '.') }} đ)</strong>
                        <p style="font-size: 11px; margin-top: 2px; color: #92400e; font-weight: 700;">⚠️ KHÁCH ĐÃ CỌC 40% — SHIPPER CHỈ THU SỐ TIỀN CÒN LẠI: {{ number_format($order->remaining_amount, 0, ',', '.') }} đ. TUYỆT ĐỐI KHÔNG THU ĐỦ {{ number_format($order->total_amount, 0, ',', '.') }} đ.</p>
                    </div>
                    <div style="font-size: 16px; font-weight: 900; color: #b45309;">
                        {{ number_format($order->remaining_amount, 0, ',', '.') }} đ
                    </div>
                </div>
            @endif
        @elseif($order->payment_method === 'bank_transfer' && $order->payment_status === 'paid')
            <div class="cod-notice" style="background: #ecfdf5; border: 1.5px solid #6ee7b7; color: #065f46;">
                <div>
                    <strong style="color: #047857; font-size: 13px;">HÌNH THỨC: CHUYỂN KHOẢN VIETQR (ĐÃ THANH TOÁN 100%)</strong>
                    <p style="font-size: 11px; margin-top: 2px; color: #047857; font-weight: 600;">⚠️ ĐÃ THANH TOÁN ĐỦ — SHIPPER TUYỆT ĐỐI KHÔNG THU TIỀN HÀNG CỦA KHÁCH.</p>
                </div>
                <div style="font-size: 16px; font-weight: 900; color: #059669;">
                    0 đ
                </div>
            </div>
        @else
            <div class="cod-notice">
                <div>
                    <strong>HÌNH THỨC: THANH TOÁN KHI NHẬN HÀNG (COD)</strong>
                    <p style="font-size: 11px; margin-top: 2px;">Cho phép khách hàng đồng kiểm kiện hàng trước khi thanh toán.</p>
                </div>
                <div style="font-size: 15px; font-weight: 800;">
                    {{ number_format($order->total_amount, 0, ',', '.') }} đ
                </div>
            </div>
        @endif

        @if($order->note)
            <div style="background: #fffbeb; border: 1px solid #fef3c7; padding: 10px 14px; border-radius: 8px; font-size: 11px; color: #92400e; margin-bottom: 20px;">
                <strong>Ghi chú đơn hàng:</strong> {{ $order->note }}
            </div>
        @endif

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-block">
                <h4>Người nhận hàng</h4>
                <p>(Ký và ghi rõ họ tên)</p>
                <div class="sig-space"></div>
            </div>
            <div class="sig-block">
                <h4>Nhân viên giao hàng</h4>
                <p>(Ký và ghi rõ họ tên)</p>
                <div class="sig-space"></div>
            </div>
        </div>

    </div>
    @empty
        <div class="container order-sheet" style="text-align: center; padding: 48px 24px;">
            <h3 style="font-size: 16px; font-weight: bold; color: #64748b;">Không có đơn hàng nào để in</h3>
            <p style="color: #94a3b8; font-size: 12px; margin-top: 8px;">Vui lòng chọn ít nhất một đơn hàng từ danh sách quản trị.</p>
        </div>
    @endforelse

</body>
</html>
