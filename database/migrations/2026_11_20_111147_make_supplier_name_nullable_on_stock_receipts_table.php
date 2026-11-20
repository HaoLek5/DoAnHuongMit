
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_receipts', function (Blueprint $table) {
            $table->string('supplier_name', 200)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Chuyển các giá trị NULL thành chuỗi rỗng trước khi đặt NOT NULL.
        DB::table('stock_receipts')
            ->whereNull('supplier_name')
            ->update(['supplier_name' => '']);

        Schema::table('stock_receipts', function (Blueprint $table) {
            $table->string('supplier_name', 200)->nullable(false)->change();
        });
    }
};
