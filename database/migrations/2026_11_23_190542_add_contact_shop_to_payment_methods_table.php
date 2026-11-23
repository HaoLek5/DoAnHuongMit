<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            if (Schema::hasTable('orders')) {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cod'");
            }

            if (Schema::hasTable('payments')) {
                DB::statement("ALTER TABLE `payments` MODIFY COLUMN `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cod'");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            if (Schema::hasTable('orders')) {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_method` ENUM('cod', 'bank_transfer') NOT NULL DEFAULT 'cod'");
            }

            if (Schema::hasTable('payments')) {
                DB::statement("ALTER TABLE `payments` MODIFY COLUMN `payment_method` ENUM('cod', 'bank_transfer') NOT NULL DEFAULT 'cod'");
            }
        }
    }
};
