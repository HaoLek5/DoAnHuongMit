<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CartController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// ─── Home & Products ────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/san-pham/{product}/json', [HomeController::class, 'getProductJson'])->name('products.json');

// ─── Chatbot AI Assistant ───────────────────────────────────────────────────
Route::post('/chatbot/message', [ChatbotController::class, 'sendMessage'])
    ->middleware('throttle:chatbot')
    ->name('chatbot.message');
Route::get('/chatbot/suggestions', [ChatbotController::class, 'getSuggestions'])->name('chatbot.suggestions');

// ─── Cart Routes ─────────────────────────────────────────────────────────────
Route::get('/gio-hang', [CartController::class, 'index'])->name('cart.index');
Route::get('/gio-hang/data', [CartController::class, 'getData'])->name('cart.data');
Route::post('/gio-hang/them', [CartController::class, 'add'])->name('cart.add');
Route::put('/gio-hang/{id}', [CartController::class, 'updateQuantity'])->name('cart.update');
Route::delete('/gio-hang/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/gio-hang/xoa-tat-ca', [CartController::class, 'clear'])->name('cart.clear');

// ─── Auth Routes ─────────────────────────────────────────────────────────────
Route::get('/login', fn () => redirect()->route('auth.login'))->name('login');

Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/dang-nhap', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('auth.login.post');

    Route::get('/dang-ky', [AuthController::class, 'showRegister'])->name('auth.register');
    Route::post('/dang-ky', [AuthController::class, 'register'])
        ->middleware('throttle:register')
        ->name('auth.register.post');

    // Quên / Đặt lại mật khẩu khi chưa đăng nhập
    Route::get('/quen-mat-khau', [AuthController::class, 'showForgotPassword'])->name('auth.forgot-password');
    Route::post('/quen-mat-khau', [AuthController::class, 'sendResetOtp'])
        ->middleware('throttle:6,1')
        ->name('auth.forgot-password.send');
    Route::post('/quen-mat-khau/gui-lai', [AuthController::class, 'resendResetOtp'])
        ->middleware('throttle:6,1')
        ->name('auth.forgot-password.resend');

    Route::get('/dat-lai-mat-khau', [AuthController::class, 'showResetPassword'])->name('auth.reset-password');
    Route::post('/dat-lai-mat-khau', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:6,1')
        ->name('auth.reset-password.post');

    // Route alias chuẩn Laravel cho forgot/reset password
    Route::get('/forgot-password', fn () => redirect()->route('auth.forgot-password'))->name('password.request');
    Route::get('/reset-password', fn () => redirect()->route('auth.reset-password'))->name('password.reset');
});

Route::post('/dang-xuat', [AuthController::class, 'logout'])
    ->name('auth.logout')
    ->middleware('auth');

// ─── Email Verification Routes ───────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/xac-thuc-email', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');
    Route::post('/xac-thuc-email', [AuthController::class, 'verifyEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.verify');
    Route::post('/xac-thuc-email/gui-lai', [AuthController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.resend');
});

// ─── Checkout & Order Placement ─────────────────────────────────────────────
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentWebhookController;

Route::middleware('auth')->group(function () {
    Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/thanh-toan', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/thanh-toan/chuyen-khoan/{session_code}', [CheckoutController::class, 'bankPayment'])->name('checkout.bank-payment');
    Route::get('/thanh-toan/chuyen-khoan/{session_code}/check-status', [CheckoutController::class, 'checkBankPaymentStatus'])->name('checkout.bank-payment.status');
    Route::post('/thanh-toan/chuyen-khoan/{session_code}/huy', [CheckoutController::class, 'cancelBankPayment'])->name('checkout.bank-payment.cancel');
    if (app()->environment(['local', 'testing'])) {
        Route::post('/thanh-toan/chuyen-khoan/{session_code}/gia-lap-thanh-cong', [CheckoutController::class, 'simulateBankPaymentSuccess'])->name('checkout.bank-payment.simulate');
    }
    Route::get('/thanh-toan/{order_code}/thanh-cong', [CheckoutController::class, 'success'])->name('checkout.success');
});

// ─── Payment Webhook (SePay / Casso / Bank Bot) ─────────────────────────────
Route::post('/api/payment/webhook', [PaymentWebhookController::class, 'handle'])->name('payment.webhook');
Route::post('/webhook/payment', [PaymentWebhookController::class, 'handle']);

// ─── Account & Addresses (User Profile) ──────────────────────────────────────
use App\Http\Controllers\AccountController;

Route::prefix('tai-khoan')->name('account.')->middleware('auth')->group(function () {
    Route::get('/', [AccountController::class, 'profile'])->name('profile');
    Route::put('/thong-tin', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::post('/gui-ma-doi-mat-khau', [AccountController::class, 'sendPasswordVerificationCode'])
        ->middleware('throttle:6,1')
        ->name('password.send-code');
    Route::put('/doi-mat-khau', [AccountController::class, 'updatePassword'])->name('password.update');

    // Addresses
    Route::get('/dia-chi', [AccountController::class, 'addresses'])->name('addresses.index');
    Route::post('/dia-chi', [AccountController::class, 'storeAddress'])->name('addresses.store');
    Route::put('/dia-chi/{address}', [AccountController::class, 'updateAddress'])->name('addresses.update');
    Route::delete('/dia-chi/{address}', [AccountController::class, 'destroyAddress'])->name('addresses.destroy');
    Route::post('/dia-chi/{address}/mac-dinh', [AccountController::class, 'setDefaultAddress'])->name('addresses.default');

    // Orders
    Route::get('/don-hang', [AccountController::class, 'orders'])->name('orders.index');
    Route::get('/don-hang/{order_code}', [AccountController::class, 'showOrder'])->name('orders.show');
    Route::put('/don-hang/{order_code}/doi-dia-chi', [AccountController::class, 'updateOrderAddress'])->name('orders.update-address');
    Route::post('/don-hang/{order_code}/huy', [AccountController::class, 'cancelOrder'])->name('orders.cancel');
});

// ─── Admin Routes ────────────────────────────────────────────────────────────
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BankTransactionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SaleCampaignController;
use App\Http\Controllers\Admin\StatisticController;
use App\Http\Controllers\Admin\StockReceiptController;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Báo cáo & Thống kê (trang chủ quản trị)
    Route::get('/', [StatisticController::class, 'index'])->name('statistics.index');
    Route::get('statistics', [StatisticController::class, 'index']);
    Route::get('dashboard', [StatisticController::class, 'index'])->name('dashboard');

    // Orders (Quản lý đơn hàng)
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('orders/bulk-action', [OrderController::class, 'bulkAction'])->name('orders.bulk-action');
    Route::get('orders/print-bulk', [OrderController::class, 'printBulk'])->name('orders.print-bulk');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::put('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::put('orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])->name('orders.update-payment-status');
    Route::get('orders/{order}/print', [OrderController::class, 'printInvoice'])->name('orders.print');

    // Bank Transactions Reconciliation (Đối soát chuyển khoản SePay / MB Bank)
    Route::get('bank-transactions', [BankTransactionController::class, 'index'])->name('bank-transactions.index');
    Route::put('bank-transactions/{bankTransaction}/reconcile', [BankTransactionController::class, 'reconcile'])->name('bank-transactions.reconcile');

    // Products
    Route::post('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
    Route::resource('products', ProductController::class);

    // Sale Campaigns (Chiến dịch Sale)
    Route::get('sale-campaigns/products-json', [SaleCampaignController::class, 'getProductsJson'])->name('sale-campaigns.products-json');
    Route::post('sale-campaigns/{sale_campaign}/toggle-status', [SaleCampaignController::class, 'toggleStatus'])->name('sale-campaigns.toggle-status');
    Route::resource('sale-campaigns', SaleCampaignController::class);

    // Stock Receipts (Nhập kho)
    Route::resource('stock-receipts', StockReceiptController::class);

    // Inventory (Kiểm kho & Sổ kho)
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

    // Categories (Popup actions)
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');

    // Attributes (Popup actions)
    Route::post('attributes', [AttributeController::class, 'store'])->name('attributes.store');
    Route::put('attributes/{attribute}', [AttributeController::class, 'update'])->name('attributes.update');
    Route::post('attributes/{attribute}/values', [AttributeController::class, 'storeValue'])->name('attributes.values.store');

    // Customers (Quản lý tài khoản khách hàng)
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::post('customers/{customer}/toggle-lock', [CustomerController::class, 'toggleLock'])->name('customers.toggle-lock');
    Route::post('customers/{customer}/reset-password', [CustomerController::class, 'resetPassword'])->name('customers.reset-password');
});
