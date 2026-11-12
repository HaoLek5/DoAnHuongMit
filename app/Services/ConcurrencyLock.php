<?php

namespace App\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConcurrencyLock
{
    /**
     * Theo dõi các khóa đang giữ trong cùng request để tránh mở/đóng lồng cùng khóa trên MySQL.
     *
     * @var array<string, int>
     */
    protected static array $heldMysqlLocks = [];

    /**
     * Thực thi callback trong vùng khóa độc quyền an toàn qua các cache driver và restart.
     *
     * Trên MySQL/MariaDB ưu tiên sử dụng advisory lock (`GET_LOCK`) ở tầng database để bảo đảm
     * đồng bộ đa tiến trình ngay cả khi hosting dùng CACHE_STORE=file/array hoặc bị xóa cache.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function run(string $key, int $seconds, int $waitSeconds, callable $callback): mixed
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $mysqlLockName = 'hm_'.sha1($key);

            if (isset(self::$heldMysqlLocks[$mysqlLockName])) {
                self::$heldMysqlLocks[$mysqlLockName]++;

                try {
                    return $callback();
                } finally {
                    self::$heldMysqlLocks[$mysqlLockName]--;
                    if (self::$heldMysqlLocks[$mysqlLockName] <= 0) {
                        unset(self::$heldMysqlLocks[$mysqlLockName]);
                    }
                }
            }

            $rows = DB::select('SELECT GET_LOCK(?, ?) AS acquired', [$mysqlLockName, max(1, $waitSeconds)]);
            $acquired = ! empty($rows) ? (int) ($rows[0]->acquired ?? 0) : 0;

            if ($acquired !== 1) {
                throw new \RuntimeException('Hệ thống đang xử lý giao dịch trùng khớp, vui lòng thử lại sau giây lát.');
            }

            self::$heldMysqlLocks[$mysqlLockName] = 1;

            try {
                return $callback();
            } finally {
                unset(self::$heldMysqlLocks[$mysqlLockName]);
                try {
                    DB::select('SELECT RELEASE_LOCK(?)', [$mysqlLockName]);
                } catch (\Throwable) {
                    // Bỏ qua nếu kết nối DB đã đóng khi dọn dẹp
                }
            }
        }

        try {
            return Cache::lock($key, $seconds)->block($waitSeconds, $callback);
        } catch (LockTimeoutException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('Cache::lock không khả dụng trên cache driver hiện tại, chuyển sang giao dịch DB.', [
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
            ]);

            return $callback();
        }
    }
}
