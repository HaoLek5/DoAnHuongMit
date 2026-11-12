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
        // Loại bỏ hẳn bảng stock_receipt_histories khỏi DB theo yêu cầu
        Schema::dropIfExists('stock_receipt_histories');

        // Tạo bảng inventory_transactions theo đặc tả chuẩn
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->enum('type', ['import', 'sale', 'return', 'adjustment']);
            $table->integer('quantity'); // có thể âm hoặc dương
            $table->enum('reference_type', ['purchase_receipt', 'order', 'manual'])->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
