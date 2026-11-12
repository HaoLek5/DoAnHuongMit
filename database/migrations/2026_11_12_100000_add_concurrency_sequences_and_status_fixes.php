<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bổ sung bảng cấp phát mã tuần tự an toàn concurrency, mở rộng order_status hỗ trợ cancel_pending
     * trên MySQL 8 và thêm index cho transaction_code để tăng tốc kiểm tra chống replay webhook.
     */
    public function up(): void
    {
        if (! Schema::hasTable('code_sequences')) {
            Schema::create('code_sequences', function (Blueprint $table): void {
                $table->string('name', 50)->primary();
                $table->unsignedBigInteger('current_number')->default(0);
                $table->timestamps();
            });
        }

        $maxOrderNum = 0;
        if (Schema::hasTable('orders')) {
            foreach (DB::table('orders')->pluck('order_code') as $code) {
                if (preg_match('/^DH([0-9]+)$/', (string) $code, $matches)) {
                    $num = (int) $matches[1];
                    if ($num > $maxOrderNum) {
                        $maxOrderNum = $num;
                    }
                }
            }
            $maxOrderId = (int) DB::table('orders')->max('id');
            if ($maxOrderNum === 0 && $maxOrderId > 0) {
                $maxOrderNum = $maxOrderId;
            }
        }

        $maxReceiptNum = 0;
        if (Schema::hasTable('stock_receipts')) {
            foreach (DB::table('stock_receipts')->pluck('code') as $code) {
                if (preg_match('/^PN([0-9]+)$/', (string) $code, $matches)) {
                    $num = (int) $matches[1];
                    if ($num > $maxReceiptNum) {
                        $maxReceiptNum = $num;
                    }
                }
            }
            $maxReceiptId = (int) DB::table('stock_receipts')->max('id');
            if ($maxReceiptNum === 0 && $maxReceiptId > 0) {
                $maxReceiptNum = $maxReceiptId;
            }
        }

        $now = now();
        $sequences = [
            'orders' => $maxOrderNum,
            'stock_receipts' => $maxReceiptNum,
        ];

        foreach ($sequences as $seqName => $maxVal) {
            DB::table('code_sequences')->insertOrIgnore([
                'name' => $seqName,
                'current_number' => $maxVal,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('code_sequences')
                ->where('name', $seqName)
                ->where('current_number', '<', $maxVal)
                ->update([
                    'current_number' => $maxVal,
                    'updated_at' => $now,
                ]);
        }

        // Mở rộng order_status thành VARCHAR(50) nếu DB tạo từ enum chưa có 'cancel_pending'
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true) && Schema::hasTable('orders')) {
            $columns = DB::select("SHOW COLUMNS FROM `orders` LIKE 'order_status'");
            if (! empty($columns)) {
                $colType = strtolower((string) ($columns[0]->Type ?? ''));
                if (str_starts_with($colType, 'enum(')) {
                    DB::statement("ALTER TABLE `orders` MODIFY COLUMN `order_status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
                }
            }
        }

        if (Schema::hasTable('payments')) {
            $hasIdx = Schema::hasIndex('payments', 'payments_transaction_code_index')
                || Schema::hasIndex('payments', ['transaction_code']);

            if (! $hasIdx && in_array($driver, ['mysql', 'mariadb'], true)) {
                $indexes = DB::select("SHOW INDEXES FROM `payments` WHERE Column_name = 'transaction_code'");
                $hasIdx = ! empty($indexes);
            }

            if (! $hasIdx) {
                Schema::table('payments', function (Blueprint $table): void {
                    $table->index('transaction_code', 'payments_transaction_code_index');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasIndex('payments', 'payments_transaction_code_index')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->dropIndex('payments_transaction_code_index');
            });
        }

        Schema::dropIfExists('code_sequences');
    }
};
