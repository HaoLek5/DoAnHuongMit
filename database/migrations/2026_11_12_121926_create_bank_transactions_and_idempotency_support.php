<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo bảng sổ cái giao dịch ngân hàng (bank_transactions) để ghi nhận mọi khoản tiền thực nhận
     * (khớp, thiếu tiền, thừa tiền, trễ hạn, đã hủy, chưa khớp mã, chuyển đúp) phục vụ Admin đối soát,
     * đồng thời lưu idempotency_key vào DB để chống double-submit bền vững qua cache clear / server restart.
     */
    public function up(): void
    {
        if (! Schema::hasTable('bank_transactions')) {
            Schema::create('bank_transactions', function (Blueprint $table): void {
                $table->id();
                $table->string('sepay_id', 100)->nullable()->index();
                $table->string('transaction_code', 100)->nullable()->index();
                $table->string('dedup_hash', 64)->unique();
                $table->foreignId('bank_payment_session_id')
                    ->nullable()
                    ->constrained('bank_payment_sessions')
                    ->nullOnDelete();
                $table->foreignId('order_id')
                    ->nullable()
                    ->constrained('orders')
                    ->nullOnDelete();
                $table->string('gateway', 50)->nullable();
                $table->string('account_number_last4', 10)->nullable();
                $table->string('transfer_type', 20)->default('in');
                $table->string('transfer_code', 50)->nullable()->index();
                $table->decimal('expected_amount', 15, 2)->nullable();
                $table->decimal('received_amount', 15, 2);
                $table->decimal('difference_amount', 15, 2)->default(0);
                $table->string('status', 40)->index();
                $table->string('reconciliation_status', 30)->default('needs_review')->index();
                $table->text('raw_content')->nullable();
                $table->text('note')->nullable();
                $table->timestamp('occurred_at')->nullable();
                $table->foreignId('reconciled_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('reconciled_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'idempotency_key')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->string('idempotency_key', 120)->nullable()->after('order_status');
                $table->unique(['user_id', 'idempotency_key'], 'orders_user_idempotency_unique');
            });
        }

        if (Schema::hasTable('bank_payment_sessions') && ! Schema::hasColumn('bank_payment_sessions', 'idempotency_key')) {
            Schema::table('bank_payment_sessions', function (Blueprint $table): void {
                $table->string('idempotency_key', 120)->nullable()->after('session_code');
                $table->unique(['user_id', 'idempotency_key'], 'bps_user_idempotency_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bank_payment_sessions') && Schema::hasColumn('bank_payment_sessions', 'idempotency_key')) {
            Schema::table('bank_payment_sessions', function (Blueprint $table): void {
                $table->dropUnique('bps_user_idempotency_unique');
                $table->dropColumn('idempotency_key');
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'idempotency_key')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropUnique('orders_user_idempotency_unique');
                $table->dropColumn('idempotency_key');
            });
        }

        Schema::dropIfExists('bank_transactions');
    }
};
