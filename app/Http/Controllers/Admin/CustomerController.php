<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Danh sách tài khoản khách hàng
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'customer')
            ->withCount('orders')
            ->withSum(['orders as total_spent' => function ($q) {
                $q->whereIn('order_status', ['confirmed', 'completed']);
            }], 'total_amount')
            ->with(['defaultAddress', 'orders' => function ($q) {
                $q->latest('id')->take(1);
            }]);

        // Tìm kiếm theo tên, email, số điện thoại
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Lọc theo trạng thái tài khoản: active / locked
        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where(function ($q) {
                    $q->whereNull('locked_until')
                        ->orWhere('locked_until', '<=', Carbon::now());
                });
            } elseif ($status === 'locked') {
                $query->where('locked_until', '>', Carbon::now());
            }
        }

        // Lọc theo đơn hàng: has_orders / no_orders
        if ($orderFilter = $request->input('orders')) {
            if ($orderFilter === 'has_orders') {
                $query->has('orders');
            } elseif ($orderFilter === 'no_orders') {
                $query->doesntHave('orders');
            }
        }

        // Sắp xếp
        $sort = $request->input('sort', 'latest');
        switch ($sort) {
            case 'oldest':
                $query->oldest('id');
                break;
            case 'most_orders':
                $query->orderByDesc('orders_count');
                break;
            case 'most_spent':
                $query->orderByDesc('total_spent');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'latest':
            default:
                $query->latest('id');
                break;
        }

        $customers = $query->paginate(12)->withQueryString();

        // Thống kê nhanh hiển thị trên các thẻ đầu trang
        $now = Carbon::now();
        $counts = [
            'total' => User::where('role', 'customer')->count(),
            'new_30_days' => User::where('role', 'customer')->where('created_at', '>=', $now->copy()->subDays(30))->count(),
            'has_orders' => User::where('role', 'customer')->has('orders')->count(),
            'locked' => User::where('role', 'customer')->where('locked_until', '>', $now)->count(),
        ];

        return view('admin.customers.index', compact('customers', 'counts'));
    }

    /**
     * Đảm bảo chỉ được thao tác trên tài khoản có vai trò khách hàng (Customer).
     */
    protected function ensureCustomer(User $customer): void
    {
        if (! $customer->isCustomer()) {
            abort(403, 'Chỉ được phép thao tác trên tài khoản khách hàng.');
        }
    }

    /**
     * Chi tiết tài khoản khách hàng
     */
    public function show(User $customer)
    {
        $this->ensureCustomer($customer);

        $customer->load([
            'addresses',
            'orders' => function ($q) {
                $q->with(['items.variant.product.primaryImage', 'payments'])
                    ->latest('id');
            },
        ]);

        // Thống kê chi tiêu của khách hàng
        $totalOrders = $customer->orders->count();
        $confirmedOrders = $customer->orders->whereIn('order_status', ['confirmed', 'completed'])->count();
        $cancelledOrders = $customer->orders->where('order_status', 'cancelled')->count();
        $totalSpent = $customer->orders->whereIn('order_status', ['confirmed', 'completed'])->sum('total_amount');
        $avgOrderValue = $confirmedOrders > 0 ? ($totalSpent / $confirmedOrders) : 0;

        $stats = [
            'total_orders' => $totalOrders,
            'confirmed_orders' => $confirmedOrders,
            'cancelled_orders' => $cancelledOrders,
            'total_spent' => $totalSpent,
            'avg_order_value' => $avgOrderValue,
        ];

        return view('admin.customers.show', compact('customer', 'stats'));
    }

    /**
     * Khóa hoặc mở khóa tài khoản khách hàng
     */
    public function toggleLock(User $customer)
    {
        if ($customer->id === Auth::id()) {
            return back()->with('error', 'Bạn không thể khóa tài khoản của chính mình.');
        }

        $this->ensureCustomer($customer);

        if ($customer->isLocked()) {
            $customer->resetLoginAttempts();

            return back()->with('success', "Đã mở khóa tài khoản khách hàng {$customer->name} thành công.");
        } else {
            $customer->update([
                'locked_until' => Carbon::now()->addYears(10),
                'login_attempts' => 5,
            ]);

            return back()->with('success', "Đã khóa tài khoản khách hàng {$customer->name}.");
        }
    }

    /**
     * Cập nhật thông tin cơ bản của khách hàng
     */
    public function update(Request $request, User $customer)
    {
        $this->ensureCustomer($customer);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($customer->id)],
        ], [
            'name.required' => 'Vui lòng nhập họ và tên khách hàng.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'email.unique' => 'Địa chỉ email này đã được sử dụng bởi tài khoản khác.',
        ]);

        $customer->update($validated);

        return back()->with('success', "Đã cập nhật thông tin khách hàng {$customer->name} thành công.");
    }

    /**
     * Đặt lại mật khẩu mới cho khách hàng
     */
    public function resetPassword(Request $request, User $customer)
    {
        $this->ensureCustomer($customer);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
        ]);

        $customer->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', "Đã đặt lại mật khẩu mới cho khách hàng {$customer->name} thành công.");
    }
}
