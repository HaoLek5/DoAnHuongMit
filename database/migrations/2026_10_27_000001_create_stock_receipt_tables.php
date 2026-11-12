<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Phiếu nhập kho (Stock Receipts)
        Schema::create('stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // PN0001, PN0002...
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('supplier_name', 200);
            $table->string('supplier_phone', 30)->nullable();
            $table->date('receipt_date');
            $table->unsignedInteger('total_quantity')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('completed'); // completed, updated, cancelled
            $table->timestamps();
        });

        // 2. Chi tiết mặt hàng trong phiếu nhập (Stock Receipt Items)
        Schema::create('stock_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_receipt_id')->constrained('stock_receipts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_receipt_items');
        Schema::dropIfExists('stock_receipts');
    }
};
