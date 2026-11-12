<?php

namespace App\Http\Controllers;

use App\Models\BankPaymentSession;
use App\Models\BankTransaction;
use App\Models\Payment;
use App\Services\ConcurrencyLock;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentWebhookController extends Controller
{
    /**
     * Nhận và xử lý Webhook thanh toán chuyển khoản từ SePay, Casso hoặc Bank Bot
     */
    public function handle(Request $request): JsonResponse
    {
        // Log metadata an toàn, tuyệt đối không log Header xác thực, API key hoặc token
        Log::info('Nhận Payment Webhook', $this->sanitizeLogContext($request));

        // 0. Xác thực Webhook bắt buộc (fail-closed: không bao giờ bỏ qua xác thực)
        $authError = $this->authenticateWebhook($request);
        if ($authError !== null) {
            return $authError;
        }

        // 1. Nhận diện gói tin kiểm tra (Test Webhook) từ SePay (chỉ chấp nhận sau khi đã xác thực)
        $content = (string) (
            $request->input('content')
            ?? $request->input('description')
            ?? $request->input('transfer_content')
            ?? $request->input('note')
            ?? ''
        );

        if ($request->input('code') === 'SEPAYTEST'
            || str_contains(strtoupper($content), 'SEPAY TEST')
            || ((int) $request->input('id') === 0 && str_contains(strtoupper((string) $request->input('gateway', '')), 'SEPAY'))) {
            Log::info('Webhook: Nhận request kiểm tra (Test Webhook) hợp lệ từ SePay.');

            return response()->json([
                'success' => true,
                'message' => 'SePay test webhook received successfully.',
            ], 200);
        }

        // 2. Hỗ trợ cấu trúc Casso (mảng transactions trong field data)
        if ($request->has('data') && is_array($request->input('data'))) {
            $results = [];
            $hasInvalidStructure = false;

            foreach ($request->input('data') as $txn) {
                if (! is_array($txn)) {
                    $hasInvalidStructure = true;

                    continue;
                }

                $txnContent = (string) ($txn['description'] ?? $txn['content'] ?? '');
                $amount = (float) ($txn['amount'] ?? $txn['transferAmount'] ?? 0);
                $reference = $txn['tid'] ?? ($txn['referenceCode'] ?? ($txn['id'] ?? null));
                $accountNumber = $txn['accountNumber']
                    ?? $txn['subAccount']
                    ?? $txn['bank_sub_acc_id']
                    ?? $request->input('accountNumber');
                $transferType = $txn['transferType'] ?? ($amount > 0 ? 'in' : 'out');

                $gateway = trim((string) ($txn['gateway'] ?? $request->input('gateway') ?? 'Casso'));
                $sepayId = $txn['id'] ?? $txn['tid'] ?? null;
                $occurredAt = $this->parseOccurredAt($txn['when'] ?? $txn['transactionDate'] ?? null);

                $res = $this->processTransaction(
                    $txnContent,
                    $amount,
                    $reference !== null ? (string) $reference : null,
                    $accountNumber !== null ? (string) $accountNumber : null,
                    (string) $transferType,
                    $gateway !== '' ? $gateway : 'Casso',
                    $sepayId !== null ? (string) $sepayId : null,
                    $occurredAt
                );
                if (($res['status_code'] ?? 200) >= 400) {
                    $hasInvalidStructure = true;
                }
                $results[] = $res;
            }

            return response()->json([
                'success' => ! $hasInvalidStructure,
                'message' => 'Xử lý danh sách giao dịch Casso hoàn tất.',
                'results' => $results,
            ], $hasInvalidStructure ? 422 : 200);
        }

        // 3. Xử lý giao dịch đơn lẻ (SePay Webhook chuẩn)
        $amount = (float) (
            $request->input('transferAmount')
            ?? $request->input('amount')
            ?? 0
        );

        $referenceCode = $request->input('referenceCode')
            ?? $request->input('reference')
            ?? $request->input('transaction_code')
            ?? $request->input('id');

        $accountNumber = $request->input('accountNumber')
            ?? $request->input('account_number');

        $transferType = $request->input('transferType');
        $gateway = trim((string) ($request->input('gateway') ?? 'SePay'));
        $sepayId = $request->input('id');
        $occurredAt = $this->parseOccurredAt($request->input('transactionDate') ?? $request->input('when'));

        $result = $this->processTransaction(
            $content,
            $amount,
            $referenceCode !== null ? (string) $referenceCode : null,
            $accountNumber !== null ? (string) $accountNumber : null,
            $transferType !== null ? (string) $transferType : null,
            $gateway !== '' ? $gateway : 'SePay',
            $sepayId !== null ? (string) $sepayId : null,
            $occurredAt
        );

        return response()->json($result, $result['status_code'] ?? 200);
    }

    /**
     * Xác thực Webhook theo cơ chế API Key header và/hoặc HMAC-SHA256 trên raw request body.
     * Tuyệt đối fail-closed khi chưa cấu hình secret hoặc thiếu/sai thông tin xác thực.
     */
    protected function authenticateWebhook(Request $request): ?JsonResponse
    {
        $configuredToken = trim((string) config('bank.webhook_token', ''));
        $configuredHmacSecret = trim((string) config('bank.webhook_hmac_secret', ''));

        if ($configuredToken === '' && $configuredHmacSecret === '') {
            Log::critical('Webhook: Chưa cấu hình PAYMENT_WEBHOOK_TOKEN hoặc PAYMENT_WEBHOOK_HMAC_SECRET — từ chối mọi request (fail-closed).');

            return response()->json([
                'success' => false,
                'message' => 'Webhook chưa được cấu hình bảo mật trên máy chủ.',
            ], 500);
        }

        // Kiểm tra API Key qua HTTP Header (không chấp nhận token qua URL query string hoặc body)
        if ($configuredToken !== '') {
            $token = $this->extractTokenFromHeaders($request);

            if ($token === null || $token === '' || ! hash_equals($configuredToken, $token)) {
                Log::warning('Webhook: Từ chối request do thiếu hoặc sai token xác thực.', [
                    'ip' => $request->ip(),
                    'has_auth_header' => $request->headers->has('Authorization'),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Xác thực Webhook thất bại.',
                ], 401);
            }
        }

        // Kiểm tra chữ ký HMAC-SHA256 trên raw body nếu có cấu hình secret HMAC
        if ($configuredHmacSecret !== '') {
            $providedSignature = (string) (
                $request->header('X-Sepay-Signature')
                ?? $request->header('X-Signature')
                ?? $request->header('X-Hmac-Signature')
                ?? ''
            );

            $providedSignature = trim($providedSignature);
            if (str_starts_with(strtolower($providedSignature), 'sha256=')) {
                $providedSignature = substr($providedSignature, 7);
            }

            $rawBody = $request->getContent();
            $expectedHex = hash_hmac('sha256', $rawBody, $configuredHmacSecret);
            $expectedBase64 = base64_encode(hash_hmac('sha256', $rawBody, $configuredHmacSecret, true));

            $isValidHmac = $providedSignature !== '' && (
                hash_equals($expectedHex, strtolower($providedSignature))
                || hash_equals($expectedBase64, $providedSignature)
            );

            if (! $isValidHmac) {
                Log::warning('Webhook: Chữ ký HMAC-SHA256 không hợp lệ hoặc bị thiếu.', [
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Chữ ký xác thực Webhook không hợp lệ.',
                ], 401);
            }
        }

        return null;
    }

    /**
     * Chỉ trích xuất Webhook API Key từ HTTP Header hợp lệ.
     */
    protected function extractTokenFromHeaders(Request $request): ?string
    {
        $authHeader = trim((string) $request->header('Authorization', ''));
        if ($authHeader !== '' && preg_match('/^(?:Apikey|Bearer)\s+(.+)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        foreach (['X-Api-Key', 'secure-token'] as $headerName) {
            $headerVal = trim((string) $request->header($headerName, ''));
            if ($headerVal !== '') {
                return $headerVal;
            }
        }

        return null;
    }

    /**
     * Lọc bỏ thông tin nhạy cảm trước khi ghi Log.
     *
     * @return array<string, mixed>
     */
    protected function sanitizeLogContext(Request $request): array
    {
        return [
            'ip' => $request->ip(),
            'id' => $request->input('id'),
            'gateway' => $request->input('gateway'),
            'transferType' => $request->input('transferType'),
            'transferAmount' => $request->input('transferAmount') ?? $request->input('amount'),
            'referenceCode' => $request->input('referenceCode') ?? $request->input('reference'),
            'accountNumber_last4' => substr(preg_replace('/\D/', '', (string) ($request->input('accountNumber') ?? '')), -4) ?: null,
            'has_content' => ! empty($request->input('content') ?? $request->input('description')),
        ];
    }

    /**
     * Phân tích thời điểm giao dịch ngân hàng từ payload webhook.
     */
    protected function parseOccurredAt(mixed $rawDate): Carbon
    {
        if (is_string($rawDate) && trim($rawDate) !== '') {
            try {
                return Carbon::parse(trim($rawDate));
            } catch (\Throwable) {
                // Fallback về thời điểm hiện tại nếu chuỗi ngày không chuẩn
            }
        }

        return now();
    }

    /**
     * Chuẩn hóa số tài khoản ngân hàng để đối chiếu chính xác.
     */
    protected function normalizeAccountNumber(?string $accountNumber): string
    {
        return strtoupper(preg_replace('/[\s\-_.]+/', '', trim((string) $accountNumber)));
    }

    /**
     * Tìm kiếm, xác minh tài khoản nhận/loại giao dịch, ghi sổ cái thực nhận (bank_transactions)
     * và khớp lệnh chuyển khoản theo nội dung (Cú pháp HMxxxxxx).
     *
     * @return array<string, mixed>
     */
    protected function processTransaction(
        string $content,
        float $amount,
        ?string $referenceCode = null,
        ?string $accountNumber = null,
        ?string $transferType = null,
        string $gateway = 'SePay',
        ?string $sepayId = null,
        ?Carbon $occurredAt = null
    ): array {
        // 1. Bắt buộc là giao dịch tiền vào (transferType = in)
        if ($transferType === null || strtolower(trim($transferType)) !== 'in') {
            Log::warning('Webhook: Từ chối giao dịch không phải tiền vào.', [
                'transferType' => $transferType,
                'referenceCode' => $referenceCode,
            ]);

            return [
                'success' => false,
                'status_code' => 422,
                'message' => 'Chỉ chấp nhận giao dịch tiền vào (transferType=in).',
            ];
        }

        // 2. Bắt buộc kiểm tra số tài khoản ngân hàng thụ hưởng khớp với cấu hình cửa hàng
        $configuredAccount = $this->normalizeAccountNumber(config('bank.account_number'));
        if ($configuredAccount === '') {
            Log::critical('Webhook: BANK_ACCOUNT_NO chưa được cấu hình — từ chối đối soát giao dịch.');

            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Tài khoản nhận tiền chưa được cấu hình trên hệ thống.',
            ];
        }

        $incomingAccount = $this->normalizeAccountNumber($accountNumber);
        if ($incomingAccount === '' || ! hash_equals($configuredAccount, $incomingAccount)) {
            Log::warning('Webhook: Tài khoản thụ hưởng không hợp lệ hoặc sai lệch, cần đối soát.', [
                'expected_last4' => substr($configuredAccount, -4),
                'received_last4' => $incomingAccount !== '' ? substr($incomingAccount, -4) : null,
                'referenceCode' => $referenceCode,
                'amount' => $amount,
            ]);

            return [
                'success' => false,
                'status_code' => 422,
                'message' => 'Số tài khoản ngân hàng thụ hưởng không khớp hoặc bị thiếu.',
            ];
        }

        // 3. Số tiền phải dương
        if ($amount <= 0) {
            return [
                'success' => false,
                'status_code' => 422,
                'message' => 'Số tiền chuyển khoản không hợp lệ.',
            ];
        }

        $occurredTime = $occurredAt ?? now();
        $accountLast4 = substr(preg_replace('/\D/', '', $incomingAccount), -4) ?: null;
        $normalizedRef = $referenceCode !== null ? trim($referenceCode) : '';
        $normalizedSepayId = ($sepayId !== null && trim($sepayId) !== '' && trim($sepayId) !== '0')
            ? trim($sepayId)
            : '';
        $externalRef = $normalizedRef !== '' ? $normalizedRef : ($normalizedSepayId !== '' ? 'SEPAY-'.$normalizedSepayId : null);

        $transferCode = null;
        if (trim($content) !== '' && preg_match('/HM(\d{6})/i', $content, $matches)) {
            $transferCode = strtoupper($matches[0]); // VD: HM829143
        }

        $dedupHash = $externalRef !== null
            ? hash('sha256', "ext:{$externalRef}|code:".($transferCode ?? '').'|amt:'.number_format($amount, 2, '.', ''))
            : null;

        $lockKey = 'hm_webhook_tx_'.sha1($dedupHash ?? ($transferCode ?? $content.'|'.$amount));

        return ConcurrencyLock::run($lockKey, 15, 10, function () use (
            $content,
            $amount,
            $gateway,
            $accountLast4,
            $normalizedRef,
            $normalizedSepayId,
            $transferCode,
            $dedupHash,
            $occurredTime
        ): array {
            // Chống trùng lặp Webhook bền vững từ DB (an toàn qua restart server và xóa cache)
            if ($dedupHash !== null) {
                $existingTx = BankTransaction::where('dedup_hash', $dedupHash)->first();
                if ($existingTx) {
                    if ($existingTx->status === 'replay_blocked') {
                        return [
                            'success' => false,
                            'status_code' => 409,
                            'message' => "Mã giao dịch {$normalizedRef} đã được sử dụng để thanh toán đơn hàng khác.",
                        ];
                    }

                    $existingOrderCode = $existingTx->order?->order_code;
                    $wasSuccessOutcome = in_array($existingTx->status, ['matched', 'overpaid', 'duplicate_tx'], true)
                        || ($existingTx->status === 'unmatched_code' && $existingTx->transfer_code === null);

                    return array_filter([
                        'success' => $wasSuccessOutcome,
                        'status_code' => 200,
                        'message' => 'Giao dịch đã được hệ thống ghi nhận trước đó (idempotent).',
                        'order_code' => $existingOrderCode,
                    ], fn ($v) => $v !== null);
                }
            }

            $baseLedgerData = [
                'sepay_id' => $normalizedSepayId !== '' ? $normalizedSepayId : null,
                'transaction_code' => $normalizedRef !== '' ? $normalizedRef : null,
                'dedup_hash' => $dedupHash ?? hash('sha256', 'noext:'.(string) Str::uuid()),
                'gateway' => $gateway,
                'account_number_last4' => $accountLast4,
                'transfer_type' => 'in',
                'transfer_code' => $transferCode,
                'received_amount' => $amount,
                'raw_content' => $content !== '' ? $content : null,
                'occurred_at' => $occurredTime,
            ];

            // 4. Giao dịch không có nội dung hoặc không chứa mã HMxxxxxx -> vẫn ghi sổ đối soát!
            if (trim($content) === '' || $transferCode === null) {
                $this->recordBankTransaction(array_merge($baseLedgerData, [
                    'expected_amount' => null,
                    'difference_amount' => $amount,
                    'status' => 'unmatched_code',
                    'reconciliation_status' => 'needs_review',
                    'note' => trim($content) === ''
                        ? 'Tiền đã vào tài khoản nhưng nội dung chuyển khoản trống, cần Admin đối soát.'
                        : 'Tiền đã vào tài khoản nhưng không chứa cú pháp HMxxxxxx, cần Admin đối soát.',
                ]));

                Log::info('Webhook: Giao dịch thực nhận không chứa cú pháp HMxxxxxx, đã ghi sổ đối soát.', [
                    'referenceCode' => $normalizedRef !== '' ? $normalizedRef : null,
                    'amount' => $amount,
                ]);

                return [
                    'success' => trim($content) !== '',
                    'status_code' => 200,
                    'message' => trim($content) === ''
                        ? 'Nội dung chuyển khoản trống (đã ghi sổ đối soát).'
                        : 'Giao dịch không chứa cú pháp HMxxxxxx của đơn hàng Hương Mít (đã ghi sổ đối soát).',
                ];
            }

            /** @var BankPaymentSession|null $session */
            $session = BankPaymentSession::where('transfer_content', $transferCode)->first();

            if (! $session) {
                $this->recordBankTransaction(array_merge($baseLedgerData, [
                    'expected_amount' => null,
                    'difference_amount' => $amount,
                    'status' => 'unmatched_code',
                    'reconciliation_status' => 'needs_review',
                    'note' => "Tiền đã vào tài khoản với mã {$transferCode} nhưng không tìm thấy phiên QR tương ứng.",
                ]));

                Log::warning("Webhook: Không tồn tại phiên thanh toán cho mã {$transferCode}, đã ghi sổ đối soát.", [
                    'referenceCode' => $normalizedRef !== '' ? $normalizedRef : null,
                    'amount' => $amount,
                ]);

                return [
                    'success' => false,
                    'status_code' => 200,
                    'message' => "Không tìm thấy phiên thanh toán cho mã {$transferCode}",
                ];
            }

            // 5. Nếu phiên thanh toán đã được xử lý thành công trước đó
            if ($session->status === 'paid' && $session->order_id) {
                $paidBySameRef = $normalizedRef !== '' && Payment::where('order_id', $session->order_id)
                    ->where('transaction_code', $normalizedRef)
                    ->exists();

                if ($paidBySameRef || $normalizedRef === '') {
                    return [
                        'success' => true,
                        'status_code' => 200,
                        'message' => "Phiên {$transferCode} đã được thanh toán trước đó.",
                        'order_code' => $session->order?->order_code,
                    ];
                }

                // Kiểm tra xem mã tham chiếu này có phải là replay từ một ĐƠN KHÁC hay không
                if (Payment::where('transaction_code', $normalizedRef)->where('status', 'paid')->exists()) {
                    $this->recordBankTransaction(array_merge($baseLedgerData, [
                        'bank_payment_session_id' => $session->id,
                        'order_id' => $session->order_id,
                        'expected_amount' => (float) $session->amount,
                        'difference_amount' => 0,
                        'status' => 'replay_blocked',
                        'reconciliation_status' => 'ignored',
                        'note' => "Từ chối replay mã giao dịch {$normalizedRef} trên phiên {$transferCode}.",
                    ]));

                    return [
                        'success' => false,
                        'status_code' => 409,
                        'message' => "Mã giao dịch {$normalizedRef} đã được sử dụng để thanh toán đơn hàng khác.",
                    ];
                }

                // Tiền chuyển thêm lần 2 vào đúng mã phiên đã thanh toán -> ghi sổ cần hoàn/đối soát!
                $this->recordBankTransaction(array_merge($baseLedgerData, [
                    'bank_payment_session_id' => $session->id,
                    'order_id' => $session->order_id,
                    'expected_amount' => (float) $session->amount,
                    'difference_amount' => $amount,
                    'status' => 'duplicate_tx',
                    'reconciliation_status' => 'needs_review',
                    'note' => "Khách chuyển khoản thêm lần 2 vào phiên đã tất toán {$transferCode} (Đơn #{$session->order?->order_code}), cần đối soát hoàn tiền.",
                ]));

                Log::warning("Webhook: Khách chuyển trùng giao dịch mới cho phiên đã thanh toán {$transferCode}.", [
                    'order_code' => $session->order?->order_code,
                    'amount' => $amount,
                    'referenceCode' => $normalizedRef,
                ]);

                return [
                    'success' => true,
                    'status_code' => 200,
                    'message' => "Phiên {$transferCode} đã thanh toán trước đó; giao dịch phát sinh thêm đã được ghi sổ đối soát.",
                    'order_code' => $session->order?->order_code,
                ];
            }

            // 6. Chặn replay tấn công trước khi xét hết hạn/thiếu tiền nếu mã ref đã trả cho đơn khác
            if ($normalizedRef !== '' && Payment::where('transaction_code', $normalizedRef)->where('status', 'paid')->exists()) {
                $this->recordBankTransaction(array_merge($baseLedgerData, [
                    'bank_payment_session_id' => $session->id,
                    'expected_amount' => (float) $session->amount,
                    'difference_amount' => 0,
                    'status' => 'replay_blocked',
                    'reconciliation_status' => 'ignored',
                    'note' => "Chặn dùng lại mã giao dịch {$normalizedRef} đã thanh toán đơn khác.",
                ]));

                Log::warning("Webhook: Từ chối replay giao dịch {$normalizedRef} cho phiên {$transferCode}.", [
                    'referenceCode' => $normalizedRef,
                ]);

                return [
                    'success' => false,
                    'status_code' => 409,
                    'message' => "Mã giao dịch {$normalizedRef} đã được sử dụng để thanh toán đơn hàng khác.",
                ];
            }

            // 7. Nếu phiên đã hết hạn hoặc bị hủy -> KHÔNG tự tạo đơn nhưng BẮT BUỘC ghi sổ đối soát tiền thực nhận!
            if ($session->status === 'cancelled' || $session->isExpired()) {
                $lateStatus = $session->status === 'cancelled' ? 'session_cancelled' : 'expired_late';

                $this->recordBankTransaction(array_merge($baseLedgerData, [
                    'bank_payment_session_id' => $session->id,
                    'expected_amount' => (float) $session->amount,
                    'difference_amount' => $amount - (float) $session->amount,
                    'status' => $lateStatus,
                    'reconciliation_status' => 'needs_review',
                    'note' => $lateStatus === 'session_cancelled'
                        ? "Tiền thực nhận cho phiên {$transferCode} đã bị hủy trước đó, cần Admin đối soát hoàn tiền/tạo đơn thủ công."
                        : "Tiền thực nhận sau khi mã QR {$transferCode} hết hạn 10 phút, cần Admin đối soát hoàn tiền/xử lý thủ công.",
                ]));

                Log::warning("Webhook: Giao dịch trễ/đã hủy cho phiên {$transferCode}, đã ghi sổ đối soát thủ công.", [
                    'session_status' => $session->status,
                    'amount' => $amount,
                    'referenceCode' => $normalizedRef !== '' ? $normalizedRef : null,
                ]);

                return [
                    'success' => false,
                    'status_code' => 200,
                    'message' => "Phiên thanh toán {$transferCode} đã hết hạn 10 phút hoặc đã bị hủy.",
                ];
            }

            // 8. Kiểm tra số tiền chuyển khoản thiếu -> KHÔNG xác nhận đơn nhưng BẮT BUỘC ghi sổ đối soát!
            if ($amount < ((float) $session->amount - 0.5)) {
                $shortfall = (float) $session->amount - $amount;

                $this->recordBankTransaction(array_merge($baseLedgerData, [
                    'bank_payment_session_id' => $session->id,
                    'expected_amount' => (float) $session->amount,
                    'difference_amount' => -$shortfall,
                    'status' => 'underpaid',
                    'reconciliation_status' => 'needs_review',
                    'note' => sprintf(
                        'Chuyển thiếu %s đ cho mã %s (Thực nhận: %s đ / Yêu cầu: %s đ).',
                        number_format($shortfall, 0, ',', '.'),
                        $transferCode,
                        number_format($amount, 0, ',', '.'),
                        number_format((float) $session->amount, 0, ',', '.')
                    ),
                ]));

                Log::warning("Webhook: Số tiền chuyển khoản thiếu ({$amount}đ < {$session->amount}đ) cho mã {$transferCode}, đã ghi sổ đối soát.", [
                    'referenceCode' => $normalizedRef !== '' ? $normalizedRef : null,
                ]);

                return [
                    'success' => false,
                    'status_code' => 200,
                    'message' => "Số tiền chuyển khoản ({$amount}đ) không đủ theo yêu cầu ({$session->amount}đ).",
                ];
            }

            // 9. Hoàn tất phiên thanh toán, trừ tồn kho, tạo đơn hàng và ghi sổ cái giao dịch
            try {
                $order = $session->completePayment(
                    $normalizedRef !== '' ? $normalizedRef : $transferCode,
                    $amount
                );

                $isOverpaid = $amount > ((float) $session->amount + 0.5);
                $excessAmount = $isOverpaid ? ($amount - (float) $session->amount) : 0;

                $this->recordBankTransaction(array_merge($baseLedgerData, [
                    'bank_payment_session_id' => $session->id,
                    'order_id' => $order->id,
                    'expected_amount' => (float) $session->amount,
                    'difference_amount' => $excessAmount,
                    'status' => $isOverpaid ? 'overpaid' : 'matched',
                    'reconciliation_status' => $isOverpaid ? 'needs_review' : 'auto_matched',
                    'reconciled_at' => $isOverpaid ? null : now(),
                    'note' => $isOverpaid
                        ? sprintf(
                            'Đơn #%s đã được tạo; khách chuyển dư %s đ cần đối soát hoàn tiền.',
                            $order->order_code,
                            number_format($excessAmount, 0, ',', '.')
                        )
                        : "Khớp tự động đơn #{$order->order_code}.",
                ]));

                if ($isOverpaid) {
                    Log::warning("Webhook: Khách hàng chuyển dư cho đơn #{$order->order_code}, đã ghi sổ đối soát.", [
                        'expected_amount' => (float) $session->amount,
                        'received_amount' => $amount,
                        'excess_amount' => $excessAmount,
                        'referenceCode' => $normalizedRef !== '' ? $normalizedRef : null,
                    ]);
                }

                Log::info("Webhook: Đã tạo đơn hàng #{$order->order_code} và trừ tồn kho thành công cho phiên {$transferCode}");

                return [
                    'success' => true,
                    'status_code' => 200,
                    'message' => "Xác nhận thanh toán và tạo đơn hàng #{$order->order_code} thành công.",
                    'order_code' => $order->order_code,
                ];
            } catch (\Exception $e) {
                $message = $e->getMessage();

                if (str_contains($message, 'đã được sử dụng')) {
                    $this->recordBankTransaction(array_merge($baseLedgerData, [
                        'bank_payment_session_id' => $session->id,
                        'expected_amount' => (float) $session->amount,
                        'difference_amount' => 0,
                        'status' => 'replay_blocked',
                        'reconciliation_status' => 'ignored',
                        'note' => $message,
                    ]));

                    Log::warning("Webhook: Từ chối giao dịch trùng mã {$normalizedRef} cho phiên {$transferCode}.", [
                        'referenceCode' => $normalizedRef,
                    ]);

                    return [
                        'success' => false,
                        'status_code' => 409,
                        'message' => $message,
                    ];
                }

                if (str_contains($message, 'không đủ số lượng tồn kho')) {
                    $this->recordBankTransaction(array_merge($baseLedgerData, [
                        'bank_payment_session_id' => $session->id,
                        'expected_amount' => (float) $session->amount,
                        'difference_amount' => $amount - (float) $session->amount,
                        'status' => 'out_of_stock',
                        'reconciliation_status' => 'needs_review',
                        'note' => "Tiền đã về tài khoản nhưng hết tồn kho khi chốt đơn {$transferCode}: {$message}",
                    ]));

                    Log::warning("Webhook: Không đủ tồn kho khi tiền về cho phiên {$transferCode}, đã ghi sổ đối soát: ".$message, [
                        'referenceCode' => $normalizedRef !== '' ? $normalizedRef : null,
                    ]);

                    return [
                        'success' => false,
                        'status_code' => 200,
                        'message' => $message,
                    ];
                }

                if (str_contains($message, 'đã bị hủy') || str_contains($message, 'hết hạn')) {
                    $raceStatus = str_contains($message, 'đã bị hủy') ? 'session_cancelled' : 'expired_late';
                    $this->recordBankTransaction(array_merge($baseLedgerData, [
                        'bank_payment_session_id' => $session->id,
                        'expected_amount' => (float) $session->amount,
                        'difference_amount' => $amount - (float) $session->amount,
                        'status' => $raceStatus,
                        'reconciliation_status' => 'needs_review',
                        'note' => $message,
                    ]));

                    Log::warning("Webhook: Không thể hoàn tất phiên {$transferCode}, đã ghi sổ đối soát: ".$message, [
                        'referenceCode' => $normalizedRef !== '' ? $normalizedRef : null,
                    ]);

                    return [
                        'success' => false,
                        'status_code' => 200,
                        'message' => $message,
                    ];
                }

                $this->recordBankTransaction(array_merge($baseLedgerData, [
                    'bank_payment_session_id' => $session->id,
                    'expected_amount' => (float) $session->amount,
                    'difference_amount' => $amount - (float) $session->amount,
                    'status' => 'error',
                    'reconciliation_status' => 'needs_review',
                    'note' => $message,
                ]));

                Log::error("Webhook: Lỗi khi xử lý tạo đơn cho phiên {$transferCode}: ".$message);

                return [
                    'success' => false,
                    'status_code' => 500,
                    'message' => 'Lỗi hệ thống khi xử lý giao dịch.',
                ];
            }
        });
    }

    /**
     * Lưu bản ghi giao dịch ngân hàng an toàn tuyệt đối (không gây lỗi trùng lặp đồng thời).
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function recordBankTransaction(array $attributes): BankTransaction
    {
        try {
            return BankTransaction::firstOrCreate(
                ['dedup_hash' => $attributes['dedup_hash']],
                $attributes
            );
        } catch (UniqueConstraintViolationException) {
            return BankTransaction::where('dedup_hash', $attributes['dedup_hash'])->firstOrFail();
        }
    }
}
