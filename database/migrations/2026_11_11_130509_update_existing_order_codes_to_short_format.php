<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        // Lấy các đơn hàng cũ theo thứ tự id tăng dần để chuẩn hóa sang DHxxxx
        $orders = Order::orderBy('id')->get();
        $counter = 1;

        foreach ($orders as $order) {
            if (str_starts_with($order->order_code, 'DH-') || ! preg_match('/^DH[0-9]{4,}$/', $order->order_code)) {
                $newCode = 'DH'.str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
                while (Order::where('order_code', $newCode)->where('id', '!=', $order->id)->exists()) {
                    $counter++;
                    $newCode = 'DH'.str_pad((string) $counter, 4, '0', STR_PAD_LEFT);
                }
                $order->update(['order_code' => $newCode]);
                $counter++;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không hoàn tác định dạng dài cũ
    }
};
