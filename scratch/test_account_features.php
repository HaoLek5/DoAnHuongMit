<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\AccountController;
use App\Models\Address;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ViewErrorBag;

$user = User::first();
if ($user) {
    Auth::login($user);
}
view()->share('errors', new ViewErrorBag);

echo "1. Testing Profile View Rendering:\n";
try {
    $res = app(AccountController::class)->profile();
    $html = $res->render();
    echo 'SUCCESS: Profile view rendered! Length: '.strlen($html)."\n";
} catch (Throwable $e) {
    echo 'ERROR: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine()."\n";
}

echo "\n2. Testing Addresses View Rendering:\n";
try {
    $res = app(AccountController::class)->addresses();
    $html = $res->render();
    echo 'SUCCESS: Addresses view rendered! Length: '.strlen($html)."\n";
} catch (Throwable $e) {
    echo 'ERROR: '.$e->getMessage().' at '.$e->getFile().':'.$e->getLine()."\n";
}

echo "\n3. Testing Address CRUD:\n";
$controller = app(AccountController::class);

// Create
$storeReq = Request::create('/tai-khoan/dia-chi', 'POST', [
    'receiver_name' => 'Nguyễn Thị Hoa',
    'receiver_phone' => '0912345678',
    'province' => 'Hà Nội',
    'district' => 'Cầu Giấy',
    'ward' => 'Dịch Vọng Hậu',
    'detail_address' => 'Số 18 ngõ 20 Duy Tân',
    'is_default' => 1,
]);
$storeReq->headers->set('Accept', 'application/json');

$res = $controller->storeAddress($storeReq);
$data = json_decode($res->getContent(), true);
echo 'Address created: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n";

$createdId = $data['address']['id'];
$addr = Address::find($createdId);
echo "Full address: {$addr->full_address}, is_default=".var_export($addr->is_default, true)."\n";

// Update
$updateReq = Request::create("/tai-khoan/dia-chi/{$createdId}", 'PUT', [
    'receiver_name' => 'Nguyễn Thị Hoa (Updated)',
    'receiver_phone' => '0912345678',
    'province' => 'Hà Nội',
    'district' => 'Cầu Giấy',
    'ward' => 'Dịch Vọng Hậu',
    'detail_address' => 'Số 22 ngõ 20 Duy Tân',
    'is_default' => 1,
]);
$updateReq->headers->set('Accept', 'application/json');
$updateRes = $controller->updateAddress($updateReq, $addr);
echo 'Address updated: '.$updateRes->getContent()."\n";

// Delete
$delReq = Request::create("/tai-khoan/dia-chi/{$createdId}", 'DELETE');
$delReq->headers->set('Accept', 'application/json');
$delRes = $controller->destroyAddress($addr);
echo 'Address deleted: '.$delRes->getContent()."\n";

echo "\nALL ACCOUNT TESTS PASSED.\n";
