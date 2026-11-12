<?php

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Env;

if (PHP_SAPI === 'apache2handler' && defined('PHP_ZTS') && (bool) PHP_ZTS) {
    // Tránh race condition CRT putenv()/getenv() giữa các worker thread trong mod_php Windows (mpm_winnt ZTS).
    Env::disablePutenv();
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('auth.login'));
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);

        // Danh sách proxy tin cậy (TRUSTED_PROXIES) được nạp qua config/trustedproxy.php sau khi .env đã sẵn sàng.

        // Chỉ miễn CSRF cho endpoint webhook từ SePay (đã có API Key / HMAC); Chatbot bắt buộc CSRF token
        $middleware->validateCsrfTokens(except: [
            'api/payment/*',
            'webhook/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
