<?php

namespace App\Models;

use App\Services\ConcurrencyLock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BankPaymentSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_code',
        'idempotency_key',
        'user_id',
        'amount',
        'bank_id',
        'bank_name',
        'account_number',
        'account_name',
        'transfer_content',
        'qr_url',
        'cart_snapshot',
        'order_data',
        'status',
        'expires_at',
        'paid_at',
        'order_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cart_snapshot' => 'array',
        'order_data' => 'array',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Relationship: User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: Order (created after payment success)
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    /**
     * Kiểm tra phiên thanh toán đã hết hạn chưa
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->status === 'pending' && now()->greaterThan($this->expires_at)) {
            $this->update(['status' => 'expired']);

            return true;
        }

        return false;
    }

    /**
     * Số giây còn lại của mã QR
     */
    public function remainingSeconds(): int
    {
        if ($this->status !== 'pending') {
            return 0;
        }

        $diff = now()->diffInSeconds($this->expires_at, false);

        return $diff > 0 ? (int) $diff : 0;
    }

    /**
     * Sinh mã nội dung chuyển khoản duy nhất (VD: HM928412)
     */
    public static function generateTransferContent(): string
    {
        do {
            $code = 'HM'.random_int(100000, 999999);
        } while (self::where('transfer_content', $code)->exists());

        return $code;
    }

    /**
     * Sinh mã session duy nhất
     */
    public static function generateSessionCode(): string
    {
        do {
            $code = 'PAY-'.strtoupper(Str::random(10));
        } while (self::where('session_code', $code)->exists());

        return $code;
    }

    /**
     * Tạo đường dẫn mã QR VietQR động
     */
    public static function generateQrUrl(
        string $bankId,
        string $accountNumber,
        float $amount,
        string $transferContent,
        string $accountName,
        string $template = 'compact2'
    ): string {
        $encodedContent = urlencode($transferContent);
        $encodedAccountName = urlencode($accountName);
        $amountInt = (int) round($amount);

        return "https://img.vietqr.io/image/{$bankId}-{$accountNumber}-{$template}.png?amount={$amountInt}&addInfo={$encodedContent}&accountName={$encodedAccountName}";
    }

    /**
     * Hoàn tất thanh toán: Kiểm tra tồn kho, trừ kho, tạo đơn hàng và xóa giỏ hàng
     */
    public function completePayment(?string $transactionCode = null, ?float $receivedAmount = null): Order
    {
        $normalizedTx = ($transactionCode !== null && trim($transactionCode) !== '')
            ? trim($transactionCode)
            : $this->transfer_content;

        return ConcurrencyLock::run('hm_payment_tx_'.sha1($normalizedTx), 15, 10, function () use ($transactionCode, $receivedAmount): Order {
            return DB::transaction(function () use ($transactionCode, $receivedAmount) {
                /** @var BankPaymentSession $session */
                $session = self::where('id', $this->id)->lockForUpdate()->firstOrFail();

                if ($session->status === 'paid' && $session->order_id) {
                    return $session->order;
                }

                if ($session->status === 'cancelled') {
                    throw new \Exception('Phiên thanh toán này đã bị hủy.');
                }

                if ($session->isExpired()) {
                    throw new \Exception('Mã QR thanh toán đã hết hạn 10 phút. Vui lòng thanh toán lại.');
                }

                $resolvedTransactionCode = $transactionCode ?: $session->transfer_content;

                if ($transactionCode !== null && $transactionCode !== '' && Payment::where('transaction_code', $transactionCode)->where('status', 'paid')->exists()) {
                    throw new \Exception("Mã giao dịch {$transactionCode} đã được sử dụng để thanh toán đơn hàng khác.");
                }

                $cartSnapshot = $session->cart_snapshot ?? [];
                if (empty($cartSnapshot)) {
                    throw new \Exception('Dữ liệu giỏ hàng trong phiên thanh toán không hợp lệ.');
                }

                // 1. Khóa các biến thể theo thứ tự ID tăng dần để tránh deadlock và kiểm tra tồn kho
                usort($cartSnapshot, fn (array $a, array $b): int => ((int) ($a['variant_id'] ?? 0)) <=> ((int) ($b['variant_id'] ?? 0)));
                $variantIds = collect($cartSnapshot)->pluck('variant_id')->filter()->unique()->sort()->values();
                $lockedVariants = ProductVariant::whereIn('id', $variantIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($cartSnapshot as $item) {
                    $variant = $lockedVariants->get($item['variant_id']);
                    if (! $variant || $variant->stock_quantity < $item['quantity']) {
                        $name = $item['product_name'] ?? 'Sản phẩm';
                        $variantTitle = $item['variant_name'] ?? '';
                        throw new \Exception("Sản phẩm '{$name}' ({$variantTitle}) không đủ số lượng tồn kho để hoàn tất đơn.");
                    }
                }

                $orderData = $session->order_data;
                $isDeposit = ! empty($orderData['is_deposit']) || ! empty($orderData['deposit_required']);
                $orderTotal = (float) ($orderData['total_amount'] ?? $session->amount);
                $depositAmount = $isDeposit ? (float) ($orderData['deposit_amount'] ?? $session->amount) : 0;
                $paidAmount = (float) $session->amount;
                $remainingAmount = $isDeposit ? max(0, $orderTotal - $depositAmount) : 0;
                $paymentMethod = $orderData['payment_method'] ?? 'bank_transfer';
                $paymentStatus = $isDeposit ? 'deposit_paid' : 'paid';

                // 2. Tạo đơn hàng (Order)
                $order = Order::create([
                    'order_code' => Order::generateOrderCode(),
                    'user_id' => $session->user_id,
                    'address_id' => $orderData['address_id'] ?? null,
                    'receiver_name' => $orderData['receiver_name'],
                    'receiver_phone' => $orderData['receiver_phone'],
                    'shipping_address' => $orderData['shipping_address'],
                    'subtotal' => $orderData['subtotal'],
                    'discount_amount' => $orderData['discount_amount'] ?? 0,
                    'shipping_fee' => $orderData['shipping_fee'] ?? 0,
                    'total_amount' => $orderTotal,
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentStatus,
                    'deposit_required' => $isDeposit,
                    'deposit_amount' => $depositAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'order_status' => 'pending', // Chờ admin xác nhận rồi in đơn (tránh sót đơn)
                    'note' => $orderData['note'] ?? null,
                ]);

                // 3. Tạo chi tiết đơn hàng (OrderItem) & Trừ tồn kho & Ghi nhận sổ kho
                foreach ($cartSnapshot as $item) {
                    $variant = ProductVariant::lockForUpdate()->find($item['variant_id']);
                    $variantTitle = $item['variant_name'] ?? 'Mặc định';
                    $sku = $item['sku'] ?? ('SP-'.str_pad($item['product_id'] ?? 0, 4, '0', STR_PAD_LEFT).'-'.$item['variant_id']);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'variant_id' => $item['variant_id'],
                        'product_name' => $item['product_name'],
                        'sku' => $sku,
                        'variant_name' => $variantTitle,
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['price'] * $item['quantity'],
                    ]);

                    // Trừ số lượng tồn kho an toàn (tránh race condition / overselling)
                    $affected = ProductVariant::where('id', $item['variant_id'])
                        ->where('stock_quantity', '>=', $item['quantity'])
                        ->decrement('stock_quantity', $item['quantity']);

                    if ($affected !== 1) {
                        $name = $item['product_name'] ?? 'Sản phẩm';
                        $variantTitle = $item['variant_name'] ?? '';
                        throw new \Exception("Sản phẩm '{$name}' ({$variantTitle}) không đủ số lượng tồn kho để hoàn tất đơn.");
                    }

                    // Cập nhật số lượng đã bán nếu sản phẩm thuộc chiến dịch Sale
                    $activeSaleItem = SaleCampaignItem::where('product_variant_id', $item['variant_id'])
                        ->whereColumn('sold_quantity', '<', 'sale_quantity')
                        ->whereHas('campaign', function ($q) {
                            $q->where('is_active', true)
                                ->where('start_time', '<=', now())
                                ->where('end_time', '>=', now());
                        })
                        ->lockForUpdate()
                        ->first();

                    if ($activeSaleItem) {
                        $saleQtyDeduct = min($item['quantity'], $activeSaleItem->remaining_quantity);
                        if ($saleQtyDeduct > 0) {
                            SaleCampaignItem::where('id', $activeSaleItem->id)
                                ->whereRaw('sold_quantity + ? <= sale_quantity', [$saleQtyDeduct])
                                ->increment('sold_quantity', $saleQtyDeduct);
                        }
                    }

                    // Ghi nhận sổ kho (InventoryTransaction)
                    InventoryTransaction::create([
                        'variant_id' => $item['variant_id'],
                        'type' => 'sale',
                        'quantity' => -$item['quantity'],
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'note' => "Xuất kho bán hàng đơn chuyển khoản VietQR #{$order->order_code}",
                        'created_by' => $session->user_id,
                        'created_at' => now(),
                    ]);
                }

                $paymentNote = $isDeposit
                    ? 'Thanh toán tiền cọc 40% cho đơn hàng COD qua mã VietQR thành công'
                    : 'Thanh toán chuyển khoản ngân hàng qua mã VietQR thành công';

                if ($receivedAmount !== null && $receivedAmount > ((float) $session->amount + 0.5)) {
                    $excessAmount = $receivedAmount - (float) $session->amount;
                    $paymentNote .= sprintf(
                        ' (Khách chuyển dư %s đ — cần đối soát hoàn tiền)',
                        number_format($excessAmount, 0, ',', '.')
                    );
                }

                // 4. Tạo bản ghi thanh toán (Payment)
                Payment::create([
                    'order_id' => $order->id,
                    'payment_type' => $isDeposit ? 'deposit' : 'final',
                    'payment_method' => 'bank_transfer',
                    'amount' => $session->amount,
                    'status' => 'paid',
                    'transaction_code' => $resolvedTransactionCode,
                    'paid_at' => now(),
                    'confirmed_at' => now(),
                    'note' => $paymentNote,
                ]);

                // 5. Cập nhật phiên thanh toán thành đã thanh toán và gán order_id
                $session->update([
                    'status' => 'paid',
                    'order_id' => $order->id,
                    'paid_at' => now(),
                ]);

                // 6. Xóa các mặt hàng đã thanh toán khỏi giỏ của người dùng
                $selectedItemIds = $orderData['selected_item_ids'] ?? [];
                if (! empty($selectedItemIds)) {
                    Cart::where('user_id', $session->user_id)->first()?->items()->whereIn('id', $selectedItemIds)->delete();
                } else {
                    Cart::where('user_id', $session->user_id)->first()?->items()->delete();
                }

                return $order;
            }, 3);
        });
    }
}
