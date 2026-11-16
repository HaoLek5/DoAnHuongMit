<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerificationMail;
use App\Models\Address;
use App\Models\EmailVerification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    /**
     * Trang thông tin tài khoản cá nhân
     */
    public function profile()
    {
        /** @var User $user */
        $user = Auth::user();
        $passwordCodeSeconds = EmailVerification::secondsUntilResend($user->email, 'change_password');

        return view('account.profile', compact('user', 'passwordCodeSeconds'));
    }

    /**
     * Cập nhật thông tin tài khoản cá nhân
     */
    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-\+\(\)\s]*$/'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ], [
            'name.required' => 'Vui lòng nhập họ và tên của bạn.',
            'name.max' => 'Họ và tên không được vượt quá 255 ký tự.',
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'email.unique' => 'Địa chỉ email này đã được sử dụng bởi tài khoản khác.',
            'phone.regex' => 'Số điện thoại chứa ký tự không hợp lệ.',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
        ]);

        $user->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin tài khoản thành công!',
                'user' => $user,
            ]);
        }

        return back()->with('account_success', 'Cập nhật thông tin cá nhân thành công!');
    }

    /**
     * Gửi mã OTP xác thực qua email trước khi đổi mật khẩu
     */
    public function sendPasswordVerificationCode(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! EmailVerification::canResend($user->email, 'change_password')) {
            $seconds = EmailVerification::secondsUntilResend($user->email, 'change_password');
            $msg = "Vui lòng đợi {$seconds} giây trước khi yêu cầu mã mới.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'seconds' => $seconds,
                ], 429);
            }

            return back()->withErrors(['verification_code' => $msg]);
        }

        $code = EmailVerification::generate($user->email, 'change_password');
        Mail::to($user->email)->send(new EmailVerificationMail($code, 'change_password', $user->name));

        $msg = "Mã xác thực 6 số đã được gửi tới email {$user->email}. Mã có hiệu lực trong vòng 15 phút.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'seconds' => 60,
            ]);
        }

        return back()->with('password_code_sent', $msg);
    }

    /**
     * Đổi mật khẩu tài khoản (yêu cầu mã xác thực OTP từ email)
     */
    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'verification_code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'verification_code.required' => 'Vui lòng nhập mã xác thực đã gửi về email.',
            'verification_code.size' => 'Mã xác thực gồm 6 chữ số.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Mật khẩu hiện tại không chính xác.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        if (! EmailVerification::verify($user->email, $validated['verification_code'], 'change_password')) {
            return back()
                ->withErrors(['verification_code' => 'Mã xác thực không chính xác hoặc đã hết hạn. Vui lòng bấm "Lấy mã xác thực" để nhận mã mới.'])
                ->withInput($request->except('password', 'password_confirmation', 'verification_code'));
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('password_success', 'Đổi mật khẩu thành công!');
    }

    /**
     * Danh sách địa chỉ giao hàng
     */
    public function addresses()
    {
        /** @var User $user */
        $user = Auth::user();
        $addresses = $user->addresses()->get();

        return view('account.addresses', compact('user', 'addresses'));
    }

    /**
     * Thêm địa chỉ giao hàng mới
     */
    public function storeAddress(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'receiver_name' => ['required', 'string', 'max:100'],
            'receiver_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\-\+\(\)\s]*$/'],
            'province' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'ward' => ['nullable', 'string', 'max:100'],
            'detail_address' => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'receiver_name.required' => 'Vui lòng nhập tên người nhận hàng.',
            'receiver_phone.required' => 'Vui lòng nhập số điện thoại người nhận.',
            'receiver_phone.regex' => 'Số điện thoại không hợp lệ.',
            'province.required' => 'Vui lòng chọn Tỉnh/Thành phố.',
            'detail_address.required' => 'Vui lòng nhập địa chỉ cụ thể (số nhà, tên đường...).',
        ]);

        // Nếu đây là địa chỉ đầu tiên của người dùng, tự động đặt làm mặc định
        $hasExisting = $user->addresses()->exists();
        $isDefault = $request->boolean('is_default') || ! $hasExisting;

        $address = $user->addresses()->create([
            'receiver_name' => $validated['receiver_name'],
            'receiver_phone' => $validated['receiver_phone'],
            'province' => $validated['province'],
            'district' => $validated['district'] ?? null,
            'ward' => $validated['ward'] ?? null,
            'detail_address' => $validated['detail_address'],
            'is_default' => $isDefault,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Thêm địa chỉ giao hàng thành công!',
                'address' => $address,
            ]);
        }

        return redirect()->route('account.addresses.index')->with('address_success', 'Thêm địa chỉ giao hàng mới thành công!');
    }

    /**
     * Cập nhật địa chỉ giao hàng
     */
    public function updateAddress(Request $request, Address $address)
    {
        /** @var User $user */
        $user = Auth::user();

        // Kiểm tra quyền sở hữu địa chỉ
        if ($address->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền chỉnh sửa địa chỉ này.');
        }

        $validated = $request->validate([
            'receiver_name' => ['required', 'string', 'max:100'],
            'receiver_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\-\+\(\)\s]*$/'],
            'province' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'ward' => ['nullable', 'string', 'max:100'],
            'detail_address' => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'receiver_name.required' => 'Vui lòng nhập tên người nhận hàng.',
            'receiver_phone.required' => 'Vui lòng nhập số điện thoại người nhận.',
            'receiver_phone.regex' => 'Số điện thoại không hợp lệ.',
            'province.required' => 'Vui lòng chọn Tỉnh/Thành phố.',
            'detail_address.required' => 'Vui lòng nhập địa chỉ cụ thể.',
        ]);

        $isDefault = $request->boolean('is_default');

        // Nếu địa chỉ này đang là mặc định duy nhất thì không cho bỏ mặc định
        if (! $isDefault && $address->is_default && $user->addresses()->count() === 1) {
            $isDefault = true;
        }

        $address->update([
            'receiver_name' => $validated['receiver_name'],
            'receiver_phone' => $validated['receiver_phone'],
            'province' => $validated['province'],
            'district' => $validated['district'] ?? null,
            'ward' => $validated['ward'] ?? null,
            'detail_address' => $validated['detail_address'],
            'is_default' => $isDefault,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật địa chỉ thành công!',
                'address' => $address,
            ]);
        }

        return redirect()->route('account.addresses.index')->with('address_success', 'Cập nhật địa chỉ thành công!');
    }

    /**
     * Xóa địa chỉ giao hàng
     */
    public function destroyAddress(Address $address): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($address->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền xóa địa chỉ này.');
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // Nếu vừa xóa địa chỉ mặc định, tự động gán địa chỉ còn lại làm mặc định
        if ($wasDefault) {
            $firstRemaining = $user->addresses()->first();
            if ($firstRemaining) {
                $firstRemaining->update(['is_default' => true]);
            }
        }

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa địa chỉ thành công!',
            ]);
        }

        return redirect()->route('account.addresses.index')->with('address_success', 'Đã xóa địa chỉ giao hàng thành công!');
    }

    /**
     * Đặt địa chỉ làm mặc định
     */
    public function setDefaultAddress(Address $address): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($address->user_id !== $user->id) {
            abort(403, 'Bạn không có quyền thao tác trên địa chỉ này.');
        }

        $address->update(['is_default' => true]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã đặt làm địa chỉ mặc định!',
            ]);
        }

        return redirect()->route('account.addresses.index')->with('address_success', 'Đã thiết lập làm địa chỉ giao hàng mặc định!');
    }

    /**
     * Danh sách đơn hàng của người dùng
     */
    public function orders(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $status = $request->query('status');

        $query = Order::where('user_id', $user->id)
            ->with(['items.variant.product.primaryImage'])
            ->latest('id');

        if ($status && in_array($status, ['pending', 'confirmed', 'delivered', 'cancel_pending', 'cancelled'], true)) {
            $query->where('order_status', $status);
        }

        $orders = $query->paginate(10)->withQueryString();

        $counts = [
            'all' => Order::where('user_id', $user->id)->count(),
            'pending' => Order::where('user_id', $user->id)->where('order_status', 'pending')->count(),
            'confirmed' => Order::where('user_id', $user->id)->where('order_status', 'confirmed')->count(),
            'delivered' => Order::where('user_id', $user->id)->where('order_status', 'delivered')->count(),
            'cancel_pending' => Order::where('user_id', $user->id)->where('order_status', 'cancel_pending')->count(),
            'cancelled' => Order::where('user_id', $user->id)->where('order_status', 'cancelled')->count(),
        ];

        return view('account.orders', compact('user', 'orders', 'status', 'counts'));
    }

    /**
     * Chi tiết đơn hàng của người dùng
     */
    public function showOrder(string $order_code)
    {
        /** @var User $user */
        $user = Auth::user();

        $order = Order::where('order_code', $order_code)
            ->where('user_id', $user->id)
            ->with(['items.variant.product.primaryImage', 'payments'])
            ->firstOrFail();

        $savedAddresses = $user->addresses()->latest('is_default')->latest('id')->get();

        return view('account.order_detail', compact('user', 'order', 'savedAddresses'));
    }

    /**
     * Người dùng đổi địa chỉ nhận hàng (khi đơn còn ở trạng thái chờ xác nhận)
     */
    public function updateOrderAddress(Request $request, string $order_code)
    {
        /** @var User $user */
        $user = Auth::user();

        $useSavedAddress = $request->filled('address_id') && $request->input('address_id') !== 'new';
        $validated = [];
        $shippingAddress = '';

        if (! $useSavedAddress) {
            $validated = $request->validate([
                'receiver_name' => ['required', 'string', 'max:100'],
                'receiver_phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\-\+\(\)\s]*$/'],
                'province' => ['required', 'string', 'max:100'],
                'district' => ['nullable', 'string', 'max:100'],
                'ward' => ['nullable', 'string', 'max:100'],
                'detail_address' => ['required', 'string', 'max:255'],
                'save_to_book' => ['nullable', 'boolean'],
            ], [
                'receiver_name.required' => 'Vui lòng nhập họ tên người nhận.',
                'receiver_phone.required' => 'Vui lòng nhập số điện thoại người nhận.',
                'receiver_phone.regex' => 'Số điện thoại không hợp lệ.',
                'province.required' => 'Vui lòng chọn Tỉnh/Thành phố.',
                'detail_address.required' => 'Vui lòng nhập địa chỉ cụ thể.',
            ]);

            $parts = array_filter([
                $validated['detail_address'],
                $validated['ward'] ?? null,
                $validated['district'] ?? null,
                $validated['province'],
            ]);
            $shippingAddress = implode(', ', $parts);
        }

        $updated = DB::transaction(function () use ($request, $user, $order_code, $useSavedAddress, $validated, $shippingAddress): bool {
            $order = Order::where('order_code', $order_code)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->order_status !== 'pending') {
                return false;
            }

            if ($useSavedAddress) {
                $address = $user->addresses()->where('id', $request->input('address_id'))->firstOrFail();
                $order->update([
                    'address_id' => $address->id,
                    'receiver_name' => $address->receiver_name,
                    'receiver_phone' => $address->receiver_phone,
                    'shipping_address' => $address->full_address,
                ]);
            } else {
                $addressId = null;
                if ($request->boolean('save_to_book')) {
                    $newAddress = $user->addresses()->create([
                        'receiver_name' => $validated['receiver_name'],
                        'receiver_phone' => $validated['receiver_phone'],
                        'province' => $validated['province'],
                        'district' => $validated['district'] ?? null,
                        'ward' => $validated['ward'] ?? null,
                        'detail_address' => $validated['detail_address'],
                        'is_default' => false,
                    ]);
                    $addressId = $newAddress->id;
                }

                $order->update([
                    'address_id' => $addressId,
                    'receiver_name' => $validated['receiver_name'],
                    'receiver_phone' => $validated['receiver_phone'],
                    'shipping_address' => $shippingAddress,
                ]);
            }

            return true;
        }, 3);

        if (! $updated) {
            return redirect()->back()->with('error', 'Chỉ có thể đổi địa chỉ khi đơn hàng đang ở trạng thái Chờ xác nhận.');
        }

        return redirect()->route('account.orders.show', $order_code)->with('success', 'Đã cập nhật địa chỉ giao hàng thành công!');
    }

    /**
     * Người dùng tự gửi yêu cầu hủy đơn hàng (khi đơn còn ở trạng thái chờ xác nhận)
     */
    public function cancelOrder(Request $request, string $order_code)
    {
        /** @var User $user */
        $user = Auth::user();
        $reason = trim((string) $request->input('cancel_reason', 'Khách hàng yêu cầu hủy đơn'));

        $cancelled = DB::transaction(function () use ($user, $order_code, $reason): bool {
            $order = Order::where('order_code', $order_code)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->order_status !== 'pending') {
                return false;
            }

            $notePrefix = $order->note ? $order->note."\n" : '';
            $order->update([
                'order_status' => 'cancel_pending',
                'note' => $notePrefix.'['.date('d/m/Y H:i').' Khách hàng]: Yêu cầu hủy đơn ('.$reason.')',
            ]);

            return true;
        }, 3);

        if (! $cancelled) {
            return redirect()->back()->with('error', 'Chỉ có thể yêu cầu hủy khi đơn hàng đang ở trạng thái Chờ xác nhận.');
        }

        return redirect()->route('account.orders.show', $order_code)->with('success', 'Đã gửi yêu cầu hủy đơn hàng. Vui lòng chờ shop xác nhận!');
    }
}
