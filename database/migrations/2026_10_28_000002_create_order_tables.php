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
        // 1. orders table
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_code', 50)->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
                $table->foreignId('address_id')->nullable()->constrained('addresses')->cascadeOnUpdate()->nullOnDelete();
                $table->string('receiver_name', 100);
                $table->string('receiver_phone', 20);
                $table->text('shipping_address');
                $table->decimal('subtotal', 15, 2);
                $table->decimal('discount_amount', 15, 2)->default(0.00);
                $table->decimal('shipping_fee', 15, 2)->default(0.00);
                $table->decimal('total_amount', 15, 2);
                $table->enum('payment_method', ['cod', 'bank_transfer'])->default('cod');
                $table->enum('payment_status', ['unpaid', 'deposit_pending', 'deposit_paid', 'paid', 'refunded'])->default('unpaid');
                $table->boolean('deposit_required')->default(false);
                $table->decimal('deposit_amount', 15, 2)->default(0.00);
                $table->decimal('paid_amount', 15, 2)->default(0.00);
                $table->decimal('remaining_amount', 15, 2)->default(0.00);
                $table->enum('order_status', ['pending', 'confirmed', 'processing', 'shipping', 'delivered', 'cancelled'])->default('pending');
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        // 2. order_items table
        if (! Schema::hasTable('order_items')) {
            Schema::create('order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnUpdate()->restrictOnDelete();
                $table->string('product_name', 200);
                $table->string('sku', 100);
                $table->string('variant_name', 255);
                $table->decimal('price', 15, 2);
                $table->unsignedInteger('quantity');
                $table->decimal('subtotal', 15, 2);
                $table->timestamps();
            });
        }

        // 3. payments table
        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnUpdate()->cascadeOnDelete();
                $table->enum('payment_type', ['deposit', 'final'])->default('final');
                $table->enum('payment_method', ['cod', 'bank_transfer'])->default('cod');
                $table->decimal('amount', 15, 2);
                $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
                $table->string('transaction_code', 100)->nullable();
                $table->string('proof_image', 500)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('confirmed_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
                $table->timestamp('confirmed_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
