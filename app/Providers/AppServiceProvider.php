<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment(['production', 'staging']) && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiting();
    }

    /**
     * Cấu hình giới hạn tần suất request để chống brute force, spam và lạm dụng API AI.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request): array {
            $email = strtolower(trim((string) $request->input('email', '')));
            $ip = (string) $request->ip();

            $tooManyResponse = function (Request $request, array $headers) {
                $retryAfter = (int) ($headers['Retry-After'] ?? 60);

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => "Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau {$retryAfter} giây.",
                    ], 429, $headers);
                }

                return response(
                    "Quá nhiều lần thử đăng nhập. Vui lòng thử lại sau {$retryAfter} giây.",
                    429,
                    $headers
                );
            };

            return [
                Limit::perMinute(5)->by('login:email-ip:'.$email.'|'.$ip)->response($tooManyResponse),
                Limit::perMinute(10)->by('login:ip:'.$ip)->response($tooManyResponse),
            ];
        });

        RateLimiter::for('register', function (Request $request): Limit {
            return Limit::perMinute(5)->by('register:ip:'.$request->ip());
        });

        RateLimiter::for('chatbot', function (Request $request): array {
            $key = $request->user()?->id
                ? 'chatbot:user:'.$request->user()->id
                : 'chatbot:ip:'.$request->ip();

            $perMinuteResponse = function (Request $request, array $headers) {
                return response()->json([
                    'success' => false,
                    'reply' => 'Dạ bạn nhắn hơi nhanh rồi ạ, vui lòng đợi khoảng 1 phút rồi nhắn tiếp cho Hương Mít nhé! 🍼',
                    'recommended_products' => [],
                    'is_out_of_scope' => false,
                ], 429, $headers);
            };

            $perDayResponse = function (Request $request, array $headers) {
                return response()->json([
                    'success' => false,
                    'reply' => 'Dạ bạn đã đạt giới hạn hỏi trợ lý ảo trong ngày hôm nay. Vui lòng quay lại vào ngày mai nhé! 🍼',
                    'recommended_products' => [],
                    'is_out_of_scope' => false,
                ], 429, $headers);
            };

            return [
                Limit::perMinute(12)->by($key)->response($perMinuteResponse),
                Limit::perDay(120)->by($key)->response($perDayResponse),
            ];
        });
    }
}
