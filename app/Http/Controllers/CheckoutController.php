<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\BankPaymentSession;
use App\Models\Cart;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\SaleCampaignItem;
use App\Models\User;
use App\Services\ConcurrencyLock;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Hiển thị trang thanh toán đơn hàng
     */
    public function index(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('auth.login')->with('info', 'Vui lòng đăng nhập để tiến hành đặt hàng.');
        }

        $user = Auth::user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $rawItems = $request->query('items');
        $selectedItemIds = $rawItems ? array_filter(explode(',', (string) $rawItems)) : [];

        if (! empty($selectedItemIds)) {
            $cart->load([
                'items' => function ($query) use ($selectedItemIds) {
                    $query->whereIn('id', $selectedItemIds)
                        ->with([
                            'product.primaryImage',
                            'product.images',
                            'variant.attributeValues.attribute',
                            'variant.activeSaleItems.campaign',
                        ]);
                },
            ]);
        } else {
            $cart->load([
                'items.product.primaryImage',
                'items.product.images',
                'items.variant.attributeValues.attribute',
                'items.variant.activeSaleItems.campaign',
            ]);
        }

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('warning', 'Giỏ hàng của bạn đang trống hoặc chưa chọn sản phẩm nào.');
        }

        // Kiểm tra tồn kho trước khi vào checkout
        foreach ($cart->items as $item) {
            if (! $item->variant || $item->variant->stock_quantity < $item->quantity) {
                return redirect()->route('cart.index')->with('error', "Sản phẩm '{$item->product->name}' (loại: {$item->variant->variant_title}) hiện chỉ còn {$item->variant->stock_quantity} sản phẩm trong kho.");
            }
        }

        // Lấy danh sách địa chỉ của user
        $addresses = $user->addresses;
        $defaultAddress = $user->defaultAddress ?? $addresses->first();

        // 63 Tỉnh/Thành Việt Nam
        $provinces = [
            'Hà Nội', 'TP Hồ Chí Minh', 'Hải Phòng', 'Đà Nẵng', 'Cần Thơ',
            'An Giang', 'Bà Rịa - Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu',
            'Bắc Ninh', 'Bến Tre', 'Bình Định', 'Bình Dương', 'Bình Phước',
            'Bình Thuận', 'Cà Mau', 'Cao Bằng', 'Đắk Lắk', 'Đắk Nông',
            'Điện Biên', 'Đồng Nai', 'Đồng Tháp', 'Gia Lai', 'Hà Giang',
            'Hà Nam', 'Hà Tĩnh', 'Hải Dương', 'Hậu Giang', 'Hòa Bình',
            'Hưng Yên', 'Khánh Hòa', 'Kiên Giang', 'Kon Tum', 'Lai Châu',
            'Lâm Đồng', 'Lạng Sơn', 'Lào Cai', 'Long An', 'Nam Định',
            'Nghệ An', 'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên',
            'Quảng Bình', 'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị',
            'Sóc Trăng', 'Sơn La', 'Tây Ninh', 'Thái Bình', 'Thái Nguyên',
            'Thanh Hóa', 'Thừa Thiên Huế', 'Tiền Giang', 'Trà Vinh', 'Tuyên Quang',
            'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái',
        ];

        $allowCod = ! $cart->items->contains(function ($item) {
            return ! ($item->product->allow_cod ?? true);
        });

        return view('checkout.index', compact('cart', 'user', 'addresses', 'defaultAddress', 'provinces', 'allowCod'));
    }

    /**
     * Xử lý xác nhận đặt hàng (COD hoặc Chuyển khoản ngân hàng VietQR)
     */
    public function process(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('auth.login')->with('info', 'Vui lòng đăng nhập để tiến hành đặt hàng.');
        }

        $user = Auth::user();
        $rawItems = $request->input('items', $request->query('items'));
        $selectedItemIds = $rawItems ? array_filter(explode(',', (string) $rawItems)) : [];

        $rawIdempotencyKey = trim((string) ($request->input('idempotency_key') ?? $request->header('X-Idempotency-Key', '')));
        $idempotencyKey = ($rawIdempotencyKey !== '' && strlen($rawIdempotencyKey) <= 120) ? $rawIdempotencyKey : '';

        if ($idempotencyKey !== '') {
            $existingOutcome = $this->findIdempotentOutcome($user->id, $idempotencyKey);
            if ($existingOutcome !== null) {
                return $this->redirectFromCheckoutOutcome($existingOutcome);
            }

            $keyHash = sha1($idempotencyKey);
            $resultCacheKey = "checkout_idem_res:{$user->id}:{$keyHash}";
            $lockKey = "checkout_idem_lock:{$user->id}:{$keyHash}";

            return ConcurrencyLock::run($lockKey, 15, 10, function () use ($request, $user, $selectedItemIds, $resultCacheKey, $idempotencyKey) {
                $dbOutcome = $this->findIdempotentOutcome($user->id, $idempotencyKey);
                if ($dbOutcome !== null) {
                    return $this->redirectFromCheckoutOutcome($dbOutcome);
                }

                $cachedOutcome = Cache::get($resultCacheKey);
                if (is_array($cachedOutcome) && ! empty($cachedOutcome['code'])) {
                    return $this->redirectFromCheckoutOutcome($cachedOutcome);
                }

                return $this->executeCheckoutProcess($request, $user, $selectedItemIds, $resultCacheKey, $idempotencyKey);
            });
        }

        return $this->executeCheckoutProcess($request, $user, $selectedItemIds);
    }

    /**
     * Tìm kiếm kết quả đặt hàng đã được lưu bền vững theo khóa idempotency trong DB
     * (bảo đảm an toàn ngay cả khi cache bị xóa hoặc server restart).
     *
     * @return array{type: string, code: string}|null
     */
    protected function findIdempotentOutcome(int $userId, string $idempotencyKey): ?array
    {
        if ($idempotencyKey === '') {
            return null;
        }

        $existingOrder = Order::where('user_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingOrder) {
            return [
                'type' => 'order',
                'code' => $existingOrder->order_code,
            ];
        }

        $existingSession = BankPaymentSession::where('user_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingSession) {
            if ($existingSession->status === 'paid' && $existingSession->order) {
                return [
                    'type' => 'order',
                    'code' => $existingSession->order->order_code,
                ];
            }

            return [
                'type' => 'session',
                'code' => $existingSession->session_code,
            ];
        }

        return null;
    }

    /**
     * Điều hướng theo kết quả đặt hàng hoặc phiên chuyển khoản.
     *
     * @param  array{type: string, code: string}  $outcome
     */
    protected function redirectFromCheckoutOutcome(array $outcome)
    {
        return $outcome['type'] === 'session'
            ? redirect()->route('checkout.bank-payment', ['session_code' => $outcome['code']])
            : redirect()->route('checkout.success', ['order_code' => $outcome['code']]);
    }

    /**
     * Thực thi kiểm tra giỏ hàng, validate và giao dịch đặt hàng nguyên tử.
     *
     * @param  array<int, string>  $selectedItemIds
     */
    protected function executeCheckoutProcess(
        Request $request,
        User $user,
        array $selectedItemIds,
        string $idempotencyResultKey = '',
        string $idempotencyKey = ''
    ) {
        $cart = Cart::where('user_id', $user->id)->first();

        if (! $cart || $cart->items()->count() === 0) {
            return redirect()->route('cart.index')->with('warning', 'Giỏ hàng của bạn đang trống.');
        }

        // Quy tắc validation
        $rules = [
            'address_type' => ['required', 'in:existing,new'],
            'payment_method' => ['required', 'in:cod,bank_transfer'],
            'note' => ['nullable', 'string', 'max:500'],
        ];

        if ($request->input('address_type') === 'existing') {
            $rules['address_id'] = ['required', 'exists:addresses,id'];
        } else {
            $rules['receiver_name'] = ['required', 'string', 'max:100'];
            $rules['receiver_phone'] = ['required', 'string', 'regex:/^([0-9]{9,11})$/'];
            $rules['province'] = ['required', 'string', 'max:100'];
            $rules['district'] = ['nullable', 'string', 'max:100'];
            $rules['ward'] = ['nullable', 'string', 'max:100'];
            $rules['detail_address'] = ['required', 'string', 'max:255'];
            $rules['save_address'] = ['nullable', 'boolean'];
        }

        $validated = $request->validate($rules, [
            'address_id.required' => 'Vui lòng chọn địa chỉ nhận hàng.',
            'receiver_name.required' => 'Vui lòng nhập họ và tên người nhận.',
            'receiver_phone.required' => 'Vui lòng nhập số điện thoại người nhận.',
            'receiver_phone.regex' => 'Số điện thoại người nhận không hợp lệ (9 - 11 chữ số).',
            'province.required' => 'Vui lòng chọn Tỉnh / Thành phố.',
            'detail_address.required' => 'Vui lòng nhập địa chỉ cụ thể (số nhà, tên đường).',
            'payment_method.in' => 'Phương thức thanh toán đã chọn không hợp lệ.',
        ]);

        $paymentMethod = $request->input('payment_method', 'cod');

        try {
            $outcome = DB::transaction(function () use ($request, $user, $cart, $selectedItemIds, $paymentMethod, $idempotencyKey): array {
                // Khóa giỏ hàng và lấy các sản phẩm hợp lệ
                $cartQuery = $cart->items()
                    ->with(['product.primaryImage', 'variant'])
                    ->orderBy('product_variant_id')
                    ->lockForUpdate();

                if (! empty($selectedItemIds)) {
                    $cartQuery->whereIn('id', $selectedItemIds);
                }
                $cartItems = $cartQuery->get();

                if ($cartItems->isEmpty()) {
                    throw new \DomainException('Không tìm thấy sản phẩm hợp lệ để thanh toán.');
                }

                // Khóa các biến thể theo thứ tự ID tăng dần để tránh deadlock và kiểm tra tồn kho trong cùng transaction
                $variantIds = $cartItems->pluck('product_variant_id')->unique()->sort()->values();
                $lockedVariants = ProductVariant::whereIn('id', $variantIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($cartItems as $item) {
                    $variant = $lockedVariants->get($item->product_variant_id);
                    if (! $variant || $variant->stock_quantity < $item->quantity) {
                        $variantName = $item->variant ? $item->variant->variant_title : '';
                        throw new \DomainException("Sản phẩm '{$item->product->name}' ({$variantName}) không đủ số lượng trong kho.");
                    }
                }

                // Kiểm tra phương thức COD đối với sản phẩm chỉ cho phép chuyển khoản
                if ($paymentMethod === 'cod') {
                    $nonCodItem = $cartItems->first(function ($item) {
                        return ! ($item->product->allow_cod ?? true);
                    });

                    if ($nonCodItem) {
                        throw new \DomainException("Sản phẩm '{$nonCodItem->product->name}' chỉ hỗ trợ thanh toán chuyển khoản, không áp dụng COD.");
                    }
                }

                // Xác định địa chỉ nhận hàng
                $addressId = null;
                $receiverName = '';
                $receiverPhone = '';
                $shippingAddress = '';

                if ($request->input('address_type') === 'existing') {
                    $address = Address::where('id', $request->input('address_id'))
                        ->where('user_id', $user->id)
                        ->firstOrFail();

                    $addressId = $address->id;
                    $receiverName = $address->receiver_name;
                    $receiverPhone = $address->receiver_phone;
                    $shippingAddress = $address->full_address;
                } else {
                    $receiverName = trim($request->input('receiver_name'));
                    $receiverPhone = trim($request->input('receiver_phone'));
                    $province = trim($request->input('province'));
                    $district = trim($request->input('district') ?? '');
                    $ward = trim($request->input('ward') ?? '');
                    $detailAddress = trim($request->input('detail_address'));

                    $addressParts = array_filter([$detailAddress, $ward, $district, $province]);
                    $shippingAddress = implode(', ', $addressParts);

                    if ($request->boolean('save_address') || $user->addresses()->count() === 0) {
                        $newAddress = Address::create([
                            'user_id' => $user->id,
                            'receiver_name' => $receiverName,
                            'receiver_phone' => $receiverPhone,
                            'province' => $province,
                            'district' => $district ?: null,
                            'ward' => $ward ?: null,
                            'detail_address' => $detailAddress,
                            'is_default' => $user->addresses()->count() === 0,
                        ]);
                        $addressId = $newAddress->id;
                    }
                }

                // Snapshot sản phẩm & tính tổng tiền
                $subtotal = 0;
                $cartSnapshot = [];
                foreach ($cartItems as $item) {
                    $lineTotal = $item->price * $item->quantity;
                    $subtotal += $lineTotal;

                    $cartSnapshot[] = [
                        'product_id' => $item->product_id,
                        'variant_id' => $item->product_variant_id,
                        'product_name' => $item->product->name,
                        'sku' => 'SP-'.str_pad($item->product_id, 4, '0', STR_PAD_LEFT).'-'.$item->product_variant_id,
                        'variant_name' => $item->variant ? $item->variant->variant_title : 'Mặc định',
                        'price' => (float) $item->price,
                        'quantity' => (int) $item->quantity,
                        'subtotal' => (float) $lineTotal,
                        'image_url' => $item->product->primaryImage?->image_url ?? null,
                    ];
                }

                $discountAmount = 0;
                $shippingFee = 0;
                $totalAmount = $subtotal - $discountAmount + $shippingFee;

                // KIỂM TRA ĐIỀU KIỆN ĐẶT CỌC:
                // Nếu đơn có giá trị > 400k mà khách đi COD thì bắt buộc cọc 40% qua chuyển khoản VietQR
                $isCodWithDeposit = ($paymentMethod === 'cod' && $totalAmount > 400000);
                $needsBankTransfer = ($paymentMethod === 'bank_transfer' || $isCodWithDeposit);

                // ────────────────────────────────────────────────────────────────
                // TRƯỜNG HỢP 1: CẦN CHUYỂN KHOẢN (VietQR 100% HOẶC CỌC 40% CHO COD > 400K)
                // Không trừ kho, không tạo đơn ngay. Tạo phiên chờ thanh toán (10 phút).
                // ────────────────────────────────────────────────────────────────
                if ($needsBankTransfer) {
                    $sessionAmount = $isCodWithDeposit ? round($totalAmount * 0.40) : $totalAmount;
                    $depositAmount = $isCodWithDeposit ? $sessionAmount : 0;
                    $remainingAmount = $isCodWithDeposit ? ($totalAmount - $depositAmount) : 0;

                    $orderData = [
                        'address_id' => $addressId,
                        'receiver_name' => $receiverName,
                        'receiver_phone' => $receiverPhone,
                        'shipping_address' => $shippingAddress,
                        'subtotal' => $subtotal,
                        'discount_amount' => $discountAmount,
                        'shipping_fee' => $shippingFee,
                        'total_amount' => $totalAmount,
                        'note' => $request->input('note'),
                        'selected_item_ids' => $selectedItemIds,
                        'payment_method' => $paymentMethod,
                        'is_deposit' => $isCodWithDeposit,
                        'deposit_required' => $isCodWithDeposit,
                        'deposit_amount' => $depositAmount,
                        'remaining_amount' => $remainingAmount,
                    ];

                    // Hủy các phiên chờ cũ của user (nếu có)
                    BankPaymentSession::where('user_id', $user->id)
                        ->where('status', 'pending')
                        ->update(['status' => 'cancelled']);

                    $transferContent = BankPaymentSession::generateTransferContent();
                    $sessionCode = BankPaymentSession::generateSessionCode();

                    $bankId = config('bank.bank_id', 'MB');
                    $bankName = config('bank.bank_name', 'Ngân hàng Quân Đội (MB Bank)');
                    $accountNumber = config('bank.account_number', '0988888888');
                    $accountName = config('bank.account_name', 'CUA HANG HUONG MIT');
                    $template = config('bank.template', 'compact2');
                    $expiryMinutes = (int) config('bank.expiry_minutes', 10);

                    $qrUrl = BankPaymentSession::generateQrUrl(
                        $bankId,
                        $accountNumber,
                        $sessionAmount,
                        $transferContent,
                        $accountName,
                        $template
                    );

                    $session = BankPaymentSession::create([
                        'session_code' => $sessionCode,
                        'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
                        'user_id' => $user->id,
                        'amount' => $sessionAmount,
                        'bank_id' => $bankId,
                        'bank_name' => $bankName,
                        'account_number' => $accountNumber,
                        'account_name' => $accountName,
                        'transfer_content' => $transferContent,
                        'qr_url' => $qrUrl,
                        'cart_snapshot' => $cartSnapshot,
                        'order_data' => $orderData,
                        'status' => 'pending',
                        'expires_at' => now()->addMinutes($expiryMinutes),
                    ]);

                    return [
                        'type' => 'session',
                        'code' => $session->session_code,
                    ];
                }

                // ────────────────────────────────────────────────────────────────
                // TRƯỜNG HỢP 2: THANH TOÁN TIỀN MẶT KHI NHẬN HÀNG (COD <= 400K KHÔNG CẦN CỌC)
                // Trừ tồn kho và tạo đơn ngay trong cùng transaction với điều kiện nguyên tử
                // ────────────────────────────────────────────────────────────────
                $order = Order::create([
                    'order_code' => Order::generateOrderCode(),
                    'user_id' => $user->id,
                    'address_id' => $addressId,
                    'receiver_name' => $receiverName,
                    'receiver_phone' => $receiverPhone,
                    'shipping_address' => $shippingAddress,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'shipping_fee' => $shippingFee,
                    'total_amount' => $totalAmount,
                    'payment_method' => 'cod',
                    'payment_status' => 'unpaid',
                    'deposit_required' => false,
                    'deposit_amount' => 0,
                    'paid_amount' => 0,
                    'remaining_amount' => $totalAmount,
                    'order_status' => 'pending',
                    'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
                    'note' => $request->input('note'),
                ]);

                // 2. Tạo chi tiết đơn hàng (OrderItem) & Trừ kho an toàn & Ghi nhận sổ kho (InventoryTransaction)
                foreach ($cartItems as $item) {
                    $variantTitle = $item->variant ? $item->variant->variant_title : 'Mặc định';
                    $sku = 'SP-'.str_pad($item->product_id, 4, '0', STR_PAD_LEFT).'-'.$item->product_variant_id;

                    // Trừ tồn kho có điều kiện nguyên tử để ngăn overselling tuyệt đối
                    $affected = ProductVariant::where('id', $item->product_variant_id)
                        ->where('stock_quantity', '>=', $item->quantity)
                        ->decrement('stock_quantity', $item->quantity);

                    if ($affected !== 1) {
                        throw new \DomainException("Sản phẩm '{$item->product->name}' ({$variantTitle}) không đủ số lượng trong kho.");
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'variant_id' => $item->product_variant_id,
                        'product_name' => $item->product->name,
                        'sku' => $sku,
                        'variant_name' => $variantTitle,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->price * $item->quantity,
                    ]);

                    // Cập nhật số lượng đã bán của chiến dịch Sale nếu có
                    $activeSaleItem = SaleCampaignItem::where('product_variant_id', $item->product_variant_id)
                        ->whereColumn('sold_quantity', '<', 'sale_quantity')
                        ->whereHas('campaign', function ($q) {
                            $q->where('is_active', true)
                                ->where('start_time', '<=', now())
                                ->where('end_time', '>=', now());
                        })
                        ->lockForUpdate()
                        ->first();

                    if ($activeSaleItem) {
                        $saleQtyDeduct = min($item->quantity, $activeSaleItem->remaining_quantity);
                        if ($saleQtyDeduct > 0) {
                            SaleCampaignItem::where('id', $activeSaleItem->id)
                                ->whereRaw('sold_quantity + ? <= sale_quantity', [$saleQtyDeduct])
                                ->increment('sold_quantity', $saleQtyDeduct);
                        }
                    }

                    // Ghi sổ biến động kho (số âm vì xuất bán giảm tồn kho)
                    InventoryTransaction::create([
                        'variant_id' => $item->product_variant_id,
                        'type' => 'sale',
                        'quantity' => -$item->quantity,
                        'reference_type' => 'order',
                        'reference_id' => $order->id,
                        'note' => "Xuất kho bán hàng đơn #{$order->order_code}",
                        'created_by' => $user->id,
                        'created_at' => now(),
                    ]);
                }

                // 3. Tạo bản ghi thanh toán (Payment)
                Payment::create([
                    'order_id' => $order->id,
                    'payment_type' => 'final',
                    'payment_method' => 'cod',
                    'amount' => $totalAmount,
                    'status' => 'pending',
                    'note' => 'Thanh toán tiền mặt khi nhận hàng (COD)',
                ]);

                // 4. Xóa các sản phẩm đã đặt khỏi giỏ hàng
                if (! empty($selectedItemIds)) {
                    $cart->items()->whereIn('id', $selectedItemIds)->delete();
                } else {
                    $cart->items()->delete();
                }

                return [
                    'type' => 'order',
                    'code' => $order->order_code,
                ];
            }, 3);

            if ($idempotencyResultKey !== '') {
                Cache::put($idempotencyResultKey, $outcome, now()->addMinutes(30));
            }

            return $this->redirectFromCheckoutOutcome($outcome);
        } catch (UniqueConstraintViolationException $e) {
            if ($idempotencyKey !== '') {
                $existingOutcome = $this->findIdempotentOutcome($user->id, $idempotencyKey);
                if ($existingOutcome !== null) {
                    return $this->redirectFromCheckoutOutcome($existingOutcome);
                }
            }

            Log::error('Lỗi trùng khóa đặt hàng: '.$e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Đơn hàng đang được xử lý, vui lòng kiểm tra lại mục Đơn hàng của tôi.');
        } catch (\DomainException $e) {
            return redirect()->back()->withInput()->with('error', 'Đặt hàng không thành công: '.$e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Lỗi đặt hàng: '.$e->getMessage());

            return redirect()->back()->withInput()->with('error', 'Đặt hàng không thành công, vui lòng thử lại sau giây lát.');
        }
    }

    /**
     * Hiển thị màn hình quét mã QR chuyển khoản ngân hàng
     */
    public function bankPayment(string $session_code)
    {
        if (! Auth::check()) {
            return redirect()->route('auth.login');
        }

        $session = BankPaymentSession::where('session_code', $session_code)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Nếu đã thanh toán rồi thì chuyển sang trang thành công
        if ($session->status === 'paid' && $session->order_id) {
            return redirect()->route('checkout.success', ['order_code' => $session->order->order_code]);
        }

        // Tự động cập nhật nếu đã quá 10 phút
        $session->isExpired();

        $remainingSeconds = $session->remainingSeconds();

        return view('checkout.bank_payment', compact('session', 'remainingSeconds'));
    }

    /**
     * API Polling: Kiểm tra trạng thái thanh toán của mã QR theo thời gian thực
     */
    public function checkBankPaymentStatus(string $session_code): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $session = BankPaymentSession::where('session_code', $session_code)
            ->where('user_id', Auth::id())
            ->first();

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Phiên thanh toán không tồn tại.'], 404);
        }

        $isExpired = $session->isExpired();
        $remainingSeconds = $session->remainingSeconds();

        return response()->json([
            'success' => true,
            'status' => $session->status,
            'is_expired' => $isExpired,
            'remaining_seconds' => $remainingSeconds,
            'order_code' => $session->order?->order_code,
            'redirect_url' => $session->status === 'paid' && $session->order
                ? route('checkout.success', ['order_code' => $session->order->order_code])
                : null,
        ]);
    }

    /**
     * Hủy phiên thanh toán QR (người dùng chủ động hủy hoặc đổi phương thức)
     */
    public function cancelBankPayment(Request $request, string $session_code)
    {
        if (! Auth::check()) {
            return redirect()->route('auth.login');
        }

        $outcome = DB::transaction(function () use ($session_code): array {
            $session = BankPaymentSession::where('session_code', $session_code)
                ->where('user_id', Auth::id())
                ->lockForUpdate()
                ->firstOrFail();

            if ($session->status === 'paid' && $session->order_id) {
                return [
                    'status' => 'paid',
                    'order_code' => $session->order?->order_code,
                ];
            }

            if ($session->status === 'pending') {
                $session->update(['status' => 'cancelled']);
            }

            return ['status' => $session->status];
        }, 3);

        if ($outcome['status'] === 'paid' && ! empty($outcome['order_code'])) {
            $successUrl = route('checkout.success', ['order_code' => $outcome['order_code']]);
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đơn hàng đã được thanh toán thành công.',
                    'redirect_url' => $successUrl,
                ]);
            }

            return redirect($successUrl);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã hủy mã QR thanh toán.',
                'redirect_url' => route('checkout.index'),
            ]);
        }

        return redirect()->route('checkout.index')->with('info', 'Đã hủy mã QR thanh toán. Giỏ hàng của bạn vẫn được giữ nguyên.');
    }

    /**
     * Giả lập thanh toán thành công (Dành cho môi trường Test / Trải nghiệm ngay trên Local)
     */
    public function simulateBankPaymentSuccess(Request $request, string $session_code)
    {
        if (! app()->environment(['local', 'testing']) || ! in_array((string) config('app.env'), ['local', 'testing'], true)) {
            abort(404);
        }

        if (! Auth::check()) {
            return redirect()->route('auth.login');
        }

        $session = BankPaymentSession::where('session_code', $session_code)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($session->status === 'paid' && $session->order) {
            return redirect()->route('checkout.success', ['order_code' => $session->order->order_code]);
        }

        if ($session->isExpired()) {
            return redirect()->back()->with('error', 'Mã QR thanh toán đã hết hạn 10 phút. Vui lòng thanh toán lại.');
        }

        try {
            $fakeTransactionCode = 'TEST-'.strtoupper(Str::random(10));
            $order = $session->completePayment($fakeTransactionCode);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'order_code' => $order->order_code,
                    'redirect_url' => route('checkout.success', ['order_code' => $order->order_code]),
                ]);
            }

            return redirect()->route('checkout.success', ['order_code' => $order->order_code])
                ->with('success', 'Giả lập chuyển khoản thành công! Đơn hàng đã được tạo và trừ tồn kho.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi giả lập thanh toán: '.$e->getMessage());
        }
    }

    /**
     * Trang thông báo đặt hàng thành công
     */
    public function success(string $order_code)
    {
        if (! Auth::check()) {
            return redirect()->route('auth.login');
        }

        $order = Order::where('order_code', $order_code)
            ->where('user_id', Auth::id())
            ->with(['items.variant.product.primaryImage', 'payments'])
            ->firstOrFail();

        return view('checkout.success', compact('order'));
    }
}
