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
        Schema::create('sale_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('banner_url')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'start_time', 'end_time']);
        });

        Schema::create('sale_campaign_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_campaign_id')->constrained('sale_campaigns')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->decimal('original_price', 12, 2);
            $table->decimal('sale_price', 12, 2);
            $table->unsignedInteger('sale_quantity');
            $table->unsignedInteger('sold_quantity')->default(0);
            $table->timestamps();

            $table->index('sale_campaign_id');
            $table->index('product_id');
            $table->index('product_variant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_campaign_items');
        Schema::dropIfExists('sale_campaigns');
    }
};
