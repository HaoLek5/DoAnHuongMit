<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CodeSequence extends Model
{
    protected $table = 'code_sequences';

    protected $primaryKey = 'name';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'current_number',
    ];

    protected $casts = [
        'current_number' => 'integer',
    ];

    /**
     * Cấp phát mã tuần tự tiếp theo an toàn concurrency (sử dụng khóa hàng PK trên code_sequences).
     */
    public static function nextCode(
        string $sequenceName,
        string $table,
        string $column,
        string $prefix,
        int $padLength = 4
    ): string {
        if (! Schema::hasTable('code_sequences')) {
            return self::fallbackNextCode($table, $column, $prefix, $padLength);
        }

        return DB::transaction(function () use ($sequenceName, $table, $column, $prefix, $padLength): string {
            $now = now();
            DB::table('code_sequences')->insertOrIgnore([
                'name' => $sequenceName,
                'current_number' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $seqRow = DB::table('code_sequences')
                ->where('name', $sequenceName)
                ->lockForUpdate()
                ->first();

            $currentNumber = $seqRow ? (int) $seqRow->current_number : 0;

            if ($currentNumber === 0) {
                $existingCodes = DB::table($table)->lockForUpdate()->pluck($column);
                $maxNum = 0;
                $pattern = '/^'.preg_quote($prefix, '/').'([0-9]+)$/';

                foreach ($existingCodes as $code) {
                    if (preg_match($pattern, (string) $code, $matches)) {
                        $num = (int) $matches[1];
                        if ($num > $maxNum) {
                            $maxNum = $num;
                        }
                    }
                }

                if ($maxNum === 0) {
                    $maxId = (int) DB::table($table)->max('id');
                    $currentNumber = $maxId;
                } else {
                    $currentNumber = $maxNum;
                }
            }

            $nextNum = $currentNumber + 1;

            do {
                $code = $prefix.str_pad((string) $nextNum, $padLength, '0', STR_PAD_LEFT);
                $exists = DB::table($table)->where($column, $code)->lockForUpdate()->exists();
                if ($exists) {
                    $nextNum++;
                }
            } while ($exists);

            DB::table('code_sequences')
                ->where('name', $sequenceName)
                ->update([
                    'current_number' => $nextNum,
                    'updated_at' => $now,
                ]);

            return $code;
        }, 3);
    }

    /**
     * Xem trước mã tuần tự tiếp theo (chỉ đọc, không cấp phát/khóa) khớp với logic nextCode().
     */
    public static function previewNextCode(
        string $sequenceName,
        string $table,
        string $column,
        string $prefix,
        int $padLength = 4
    ): string {
        $currentNumber = 0;

        if (Schema::hasTable('code_sequences')) {
            $seqRow = DB::table('code_sequences')->where('name', $sequenceName)->first();
            $currentNumber = $seqRow ? (int) $seqRow->current_number : 0;
        }

        if ($currentNumber === 0) {
            $maxNum = 0;
            $pattern = '/^'.preg_quote($prefix, '/').'([0-9]+)$/';
            foreach (DB::table($table)->pluck($column) as $code) {
                if (preg_match($pattern, (string) $code, $matches)) {
                    $num = (int) $matches[1];
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            }

            $currentNumber = $maxNum > 0 ? $maxNum : (int) DB::table($table)->max('id');
        }

        $nextNum = $currentNumber + 1;

        do {
            $code = $prefix.str_pad((string) $nextNum, $padLength, '0', STR_PAD_LEFT);
            $exists = DB::table($table)->where($column, $code)->exists();
            if ($exists) {
                $nextNum++;
            }
        } while ($exists);

        return $code;
    }

    /**
     * Dự phòng khi bảng code_sequences chưa tồn tại (ví dụ trước lúc chạy migration).
     */
    protected static function fallbackNextCode(string $table, string $column, string $prefix, int $padLength): string
    {
        $maxNum = 0;
        $pattern = '/^'.preg_quote($prefix, '/').'([0-9]+)$/';
        foreach (DB::table($table)->pluck($column) as $code) {
            if (preg_match($pattern, (string) $code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum > 0 ? ($maxNum + 1) : (((int) DB::table($table)->max('id')) + 1);

        do {
            $code = $prefix.str_pad((string) $nextNum, $padLength, '0', STR_PAD_LEFT);
            $nextNum++;
        } while (DB::table($table)->where($column, $code)->exists());

        return $code;
    }
}
