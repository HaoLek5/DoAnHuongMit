<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticController extends Controller
{
    /**
     * Giao diện Thống kê & Báo cáo tổng thể
     */
    public function index(Request $request)
    {
        $period = $request->input('period', '7days');
        $now = Carbon::now();

        // Xác định khoảng thời gian
        switch ($period) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = 'Hôm nay ('.$startDate->format('d/m/Y').')';
                $prevStartDate = $startDate->copy()->subDay();
                $prevEndDate = $endDate->copy()->subDay();
                break;

            case 'yesterday':
                $startDate = $now->copy()->subDay()->startOfDay();
                $endDate = $now->copy()->subDay()->endOfDay();
                $periodLabel = 'Hôm qua ('.$startDate->format('d/m/Y').')';
                $prevStartDate = $startDate->copy()->subDay();
                $prevEndDate = $endDate->copy()->subDay();
                break;

            case '30days':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = '30 ngày qua ('.$startDate->format('d/m/Y').' - '.$endDate->format('d/m/Y').')';
                $prevStartDate = $startDate->copy()->subDays(30);
                $prevEndDate = $startDate->copy()->subSecond();
                break;

            case 'this_month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = 'Tháng '.$now->format('m/Y');
                $prevStartDate = $now->copy()->subMonth()->startOfMonth();
                $prevEndDate = $now->copy()->subMonth()->endOfMonth();
                break;

            case 'last_month':
                $startDate = $now->copy()->subMonth()->startOfMonth();
                $endDate = $now->copy()->subMonth()->endOfMonth();
                $periodLabel = 'Tháng trước ('.$startDate->format('m/Y').')';
                $prevStartDate = $startDate->copy()->subMonth()->startOfMonth();
                $prevEndDate = $startDate->copy()->subMonth()->endOfMonth();
                break;

            case 'this_year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = 'Năm '.$now->format('Y');
                $prevStartDate = $now->copy()->subYear()->startOfYear();
                $prevEndDate = $now->copy()->subYear()->endOfYear();
                break;

            case 'custom':
                $fromInput = $request->input('from_date');
                $toInput = $request->input('to_date');

                $startDate = $fromInput ? Carbon::parse($fromInput)->startOfDay() : $now->copy()->subDays(6)->startOfDay();
                $endDate = $toInput ? Carbon::parse($toInput)->endOfDay() : $now->copy()->endOfDay();

                if ($startDate->gt($endDate)) {
                    $tmp = $startDate;
                    $startDate = $endDate->copy()->startOfDay();
                    $endDate = $tmp->copy()->endOfDay();
                }

                $periodLabel = $startDate->format('d/m/Y').' - '.$endDate->format('d/m/Y');
                $diffDays = $startDate->diffInDays($endDate) + 1;
                $prevStartDate = $startDate->copy()->subDays($diffDays);
                $prevEndDate = $startDate->copy()->subSecond();
                break;

            case '7days':
            default:
                $period = '7days';
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $periodLabel = '7 ngày qua ('.$startDate->format('d/m/Y').' - '.$endDate->format('d/m/Y').')';
                $prevStartDate = $startDate->copy()->subDays(7);
                $prevEndDate = $startDate->copy()->subSecond();
                break;
        }

        // Danh sách trạng thái đơn hàng hợp lệ để tính doanh thu
        $validStatuses = ['confirmed', 'processing', 'shipping', 'delivered'];

        // 1. CHỈ SỐ DOANH THU & ĐƠN HÀNG TRONG KỲ
        $periodOrdersQuery = Order::whereBetween('created_at', [$startDate, $endDate]);
        $totalOrders = (clone $periodOrdersQuery)->count();

        $revenue = (clone $periodOrdersQuery)
            ->whereIn('order_status', $validStatuses)
            ->sum('total_amount');

        $confirmedOrdersCount = (clone $periodOrdersQuery)
            ->whereIn('order_status', $validStatuses)
            ->count();

        $cancelledOrdersCount = (clone $periodOrdersQuery)
            ->whereIn('order_status', ['cancelled', 'cancel_pending'])
            ->count();

        $cancelRate = $totalOrders > 0 ? round(($cancelledOrdersCount / $totalOrders) * 100, 1) : 0;
        $averageOrderValue = $confirmedOrdersCount > 0 ? round($revenue / $confirmedOrdersCount) : 0;

        // So sánh với kỳ trước
        $prevRevenue = Order::whereBetween('created_at', [$prevStartDate, $prevEndDate])
            ->whereIn('order_status', $validStatuses)
            ->sum('total_amount');

        $prevTotalOrders = Order::whereBetween('created_at', [$prevStartDate, $prevEndDate])->count();

        $revenueGrowth = $prevRevenue > 0 ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1) : ($revenue > 0 ? 100 : 0);
        $orderGrowth = $prevTotalOrders > 0 ? round((($totalOrders - $prevTotalOrders) / $prevTotalOrders) * 100, 1) : ($totalOrders > 0 ? 100 : 0);

        // Tổng sản phẩm bán ra
        $totalUnitsSold = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate, $validStatuses) {
            $q->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('order_status', $validStatuses);
        })->sum('quantity');

        // Số lượng khách hàng mới đăng ký
        $newCustomersCount = User::where('role', 'customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // 2. BIỂU ĐỒ THEO THỜI GIAN
        // Nếu khoảng thời gian <= 45 ngày: biểu diễn theo từng ngày; nếu dài hơn: theo từng tháng
        $chartLabels = [];
        $chartRevenues = [];
        $chartOrders = [];

        $diffDays = $startDate->diffInDays($endDate);
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $daySql = $isSqlite ? "strftime('%Y-%m-%d', created_at)" : 'DATE(created_at)';
        $monthSql = $isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        if ($diffDays <= 45) {
            $datePeriod = CarbonPeriod::create($startDate->copy()->startOfDay(), '1 day', $endDate->copy()->startOfDay());

            // Query nhóm theo ngày
            $dailyStats = Order::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw("{$daySql} as date"),
                    DB::raw("SUM(CASE WHEN order_status IN ('".implode("','", $validStatuses)."') THEN total_amount ELSE 0 END) as daily_revenue"),
                    DB::raw('COUNT(id) as daily_orders')
                )
                ->groupBy('date')
                ->get()
                ->keyBy('date');

            foreach ($datePeriod as $dt) {
                $dateKey = $dt->format('Y-m-d');
                $chartLabels[] = $dt->format('d/m');
                $stat = $dailyStats->get($dateKey);
                $chartRevenues[] = $stat ? (float) $stat->daily_revenue : 0;
                $chartOrders[] = $stat ? (int) $stat->daily_orders : 0;
            }
        } else {
            // Nhóm theo từng tháng
            $monthPeriod = CarbonPeriod::create($startDate->copy()->startOfMonth(), '1 month', $endDate->copy()->startOfMonth());

            $monthlyStats = Order::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw("{$monthSql} as ym"),
                    DB::raw("SUM(CASE WHEN order_status IN ('".implode("','", $validStatuses)."') THEN total_amount ELSE 0 END) as monthly_revenue"),
                    DB::raw('COUNT(id) as monthly_orders')
                )
                ->groupBy('ym')
                ->get()
                ->keyBy('ym');

            foreach ($monthPeriod as $dt) {
                $ymKey = $dt->format('Y-m');
                $chartLabels[] = 'T'.$dt->format('m/Y');
                $stat = $monthlyStats->get($ymKey);
                $chartRevenues[] = $stat ? (float) $stat->monthly_revenue : 0;
                $chartOrders[] = $stat ? (int) $stat->monthly_orders : 0;
            }
        }

        // 3. PHÂN BỐ TRẠNG THÁI ĐƠN HÀNG
        $statusCountsRaw = (clone $periodOrdersQuery)
            ->select('order_status', DB::raw('COUNT(*) as count'))
            ->groupBy('order_status')
            ->pluck('count', 'order_status')
            ->toArray();

        $allStatuses = [
            'pending' => ['label' => 'Chờ xác nhận', 'color' => 'bg-amber-500', 'textColor' => 'text-amber-600', 'bgColor' => 'bg-amber-50'],
            'confirmed' => ['label' => 'Đã xác nhận', 'color' => 'bg-emerald-500', 'textColor' => 'text-emerald-600', 'bgColor' => 'bg-emerald-50'],
            'cancel_pending' => ['label' => 'Chờ hủy', 'color' => 'bg-rose-500', 'textColor' => 'text-rose-600', 'bgColor' => 'bg-rose-50'],
            'cancelled' => ['label' => 'Đã hủy', 'color' => 'bg-slate-400', 'textColor' => 'text-slate-500', 'bgColor' => 'bg-slate-100'],
            'processing' => ['label' => 'Đang đóng gói', 'color' => 'bg-indigo-500', 'textColor' => 'text-indigo-600', 'bgColor' => 'bg-indigo-50'],
            'shipping' => ['label' => 'Đang giao hàng', 'color' => 'bg-blue-500', 'textColor' => 'text-blue-600', 'bgColor' => 'bg-blue-50'],
            'delivered' => ['label' => 'Giao thành công', 'color' => 'bg-teal-500', 'textColor' => 'text-teal-600', 'bgColor' => 'bg-teal-50'],
        ];

        $statusBreakdown = [];
        foreach ($allStatuses as $key => $meta) {
            $cnt = $statusCountsRaw[$key] ?? 0;
            $pct = $totalOrders > 0 ? round(($cnt / $totalOrders) * 100, 1) : 0;
            if ($cnt > 0 || in_array($key, ['pending', 'confirmed', 'cancel_pending', 'cancelled'])) {
                $statusBreakdown[$key] = array_merge($meta, [
                    'count' => $cnt,
                    'percentage' => $pct,
                ]);
            }
        }

        // 4. TOP 5 SẢN PHẨM BÁN CHẠY NHẤT TRONG KỲ
        $topProducts = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate, $validStatuses) {
            $q->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('order_status', $validStatuses);
        })
            ->select(
                'product_name',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue'),
                DB::raw('MIN(variant_id) as sample_variant_id')
            )
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        // 5. PHƯƠNG THỨC THANH TOÁN
        $paymentMethodsRaw = (clone $periodOrdersQuery)
            ->select('payment_method', DB::raw('COUNT(*) as total_orders'), DB::raw('SUM(total_amount) as total_sum'))
            ->groupBy('payment_method')
            ->get();

        $paymentBreakdown = [];
        $methodLabels = [
            'cod' => 'Thanh toán khi nhận hàng (COD)',
            'bank_transfer' => 'Chuyển khoản ngân hàng',
            'vnpay' => 'Cổng thanh toán VNPay',
            'momo' => 'Ví MoMo',
        ];

        foreach ($paymentMethodsRaw as $pm) {
            $methodKey = $pm->payment_method ?: 'cod';
            $paymentBreakdown[] = [
                'key' => $methodKey,
                'label' => $methodLabels[$methodKey] ?? strtoupper($methodKey),
                'order_count' => $pm->total_orders,
                'revenue' => (float) $pm->total_sum,
                'percentage' => $totalOrders > 0 ? round(($pm->total_orders / $totalOrders) * 100, 1) : 0,
            ];
        }

        // 6. CẢNH BÁO TỒN KHO SẮP HẾT (Toàn hệ thống)
        $lowStockVariants = ProductVariant::with(['product.category', 'attributeValues.attribute'])
            ->where('stock_quantity', '<=', 10)
            ->orderBy('stock_quantity', 'asc')
            ->take(8)
            ->get();

        // 7. TOÀN CẢNH KINH DOANH LŨY KẾ (ALL-TIME)
        $allTimeStats = [
            'total_revenue' => Order::whereIn('order_status', $validStatuses)->sum('total_amount'),
            'total_orders' => Order::count(),
            'total_customers' => User::where('role', 'customer')->count(),
            'total_products' => Product::count(),
        ];

        return view('admin.statistics.index', compact(
            'period',
            'periodLabel',
            'startDate',
            'endDate',
            'revenue',
            'revenueGrowth',
            'totalOrders',
            'orderGrowth',
            'confirmedOrdersCount',
            'cancelledOrdersCount',
            'cancelRate',
            'averageOrderValue',
            'totalUnitsSold',
            'newCustomersCount',
            'chartLabels',
            'chartRevenues',
            'chartOrders',
            'statusBreakdown',
            'topProducts',
            'paymentBreakdown',
            'lowStockVariants',
            'allTimeStats'
        ));
    }
}
