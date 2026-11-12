<?php

require_once __DIR__.'/../../../../../../laragon/www/haomilk/vendor/autoload.php';
$app = require_once __DIR__.'/../../../../../../laragon/www/haomilk/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Admin\StockReceiptController;
use App\Models\ProductVariant;
use App\Models\StockReceipt;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

echo "=== TEST 1: GET FIRST PRODUCT & VARIANT ===\n";
$variant = ProductVariant::first();
if (! $variant) {
    echo "No variant found!\n";
    exit(1);
}
$initialStock = $variant->stock_quantity;
echo "Found Variant ID: {$variant->id}, Product ID: {$variant->product_id}, Initial Stock: {$initialStock}\n";

$admin = User::first();
if (! $admin) {
    echo "No user found!\n";
    exit(1);
}

echo "\n=== TEST 2: CREATE STOCK RECEIPT VIA CONTROLLER ===\n";
$controller = new StockReceiptController;

// Simulate authenticated user
auth()->login($admin);

$request = Request::create('/admin/stock-receipts', 'POST', [
    'supplier_name' => 'Công ty FrieslandCampina Việt Nam',
    'supplier_phone' => '02838275555',
    'receipt_date' => date('Y-m-d'),
    'notes' => 'Lô hàng nhập đầu tuần kiểm thử',
    'items' => [
        [
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => 15,
            'unit_cost' => 350000,
            'notes' => 'Hàng nguyên seal',
        ],
    ],
]);
$request->headers->set('Accept', 'application/json');

$response = $controller->store($request);
$data = json_decode($response->getContent(), true);
echo 'Store response: '.($data['success'] ? 'SUCCESS' : 'FAILED')."\n";
echo 'Message: '.($data['message'] ?? 'none')."\n";

$receiptId = $data['receipt']['id'];
$receipt = StockReceipt::find($receiptId);
echo "Created Receipt Code: {$receipt->code}, Total Qty: {$receipt->total_quantity}, Total Amount: {$receipt->total_amount}\n";

$variant->refresh();
echo "New stock after receipt: {$variant->stock_quantity} (Expected: ".($initialStock + 15).")\n";
assert($variant->stock_quantity === ($initialStock + 15), 'Stock quantity should have increased by 15');

echo "\n=== TEST 3: UPDATE STOCK RECEIPT (EDIT QUANTITY & DETAILS) ===\n";
// Update quantity from 15 to 20 (+5 more stock)
$updateRequest = Request::create("/admin/stock-receipts/{$receipt->id}", 'PUT', [
    'supplier_name' => 'Công ty FrieslandCampina Việt Nam (Đã cập nhật)',
    'supplier_phone' => '02838275555',
    'receipt_date' => date('Y-m-d'),
    'notes' => 'Đã điều chỉnh thêm 5 hộp giao bù',
    'items' => [
        [
            'id' => $receipt->items->first()->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => 20, // +5
            'unit_cost' => 350000,
            'notes' => 'Hàng nguyên seal + 5 hộp bù',
        ],
    ],
]);
$updateRequest->headers->set('Accept', 'application/json');

$updateResponse = $controller->update($updateRequest, $receipt);
$updateData = json_decode($updateResponse->getContent(), true);
echo 'Update response: '.($updateData['success'] ? 'SUCCESS' : 'FAILED')."\n";
echo 'Message: '.($updateData['message'] ?? 'none')."\n";

$variant->refresh();
echo "New stock after update: {$variant->stock_quantity} (Expected: ".($initialStock + 20).")\n";
assert($variant->stock_quantity === ($initialStock + 20), 'Stock quantity should have increased to 20');

echo "\n=== TEST 4: CHECK RECEIPT HISTORY ===\n";
$historyResponse = $controller->history($receipt);
$historyData = json_decode($historyResponse->getContent(), true);
echo 'History count: '.count($historyData['histories'])."\n";
foreach ($historyData['histories'] as $h) {
    echo "- [{$h['action']}] {$h['description']}\n";
}

echo "\n=== TEST 5: RENDER VIEW admin.stock_receipts.index ===\n";
$indexRequest = Request::create('/admin/stock-receipts', 'GET');
$indexView = $controller->index($indexRequest);
$renderedHtml = $indexView->render();
echo 'Rendered HTML length: '.strlen($renderedHtml)."\n";
assert(strlen($renderedHtml) > 1000, 'Rendered HTML should be non-empty');

echo "\n=== ALL TESTS PASSED SUCCESSFULLY! ===\n";
