<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Display shopping cart page or return JSON for AJAX
     */
    public function index(Request $request)
    {
        if (! Auth::check()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'is_guest' => true,
                    'count' => 0,
                    'total' => 0,
                    'items' => [],
                ]);
            }

            return redirect()->route('auth.login')->with('info', 'Vui lòng đăng nhập để xem giỏ hàng của bạn.');
        }

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
        $cart->load([
            'items.product.primaryImage',
            'items.product.images',
            'items.product.category',
            'items.product.activeSaleItems.campaign',
            'items.variant.attributeValues.attribute',
            'items.variant.activeSaleItems.campaign',
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_guest' => false,
                'count' => $cart->total_quantity,
                'total' => $cart->total_price,
                'items' => $cart->items,
            ]);
        }

        return view('cart.index', compact('cart'));
    }

    /**
     * Get cart summary data for header & slide-over drawer
     */
    public function getData(): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => true,
                'is_guest' => true,
                'count' => 0,
                'total' => 0,
                'items' => [],
            ]);
        }

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
        $cart->load([
            'items.product.primaryImage',
            'items.product.images',
            'items.product.activeSaleItems.campaign',
            'items.variant.attributeValues.attribute',
            'items.variant.activeSaleItems.campaign',
        ]);

        return response()->json([
            'success' => true,
            'is_guest' => false,
            'count' => $cart->total_quantity,
            'total' => $cart->total_price,
            'items' => $cart->items,
        ]);
    }

    /**
     * Add product variant to shopping cart
     */
    public function add(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'need_login' => true,
                'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.',
            ], 401);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);
        $variant = ProductVariant::with(['product', 'attributeValues'])->findOrFail($validated['product_variant_id']);

        // Verify product matches variant
        if ($variant->product_id != $validated['product_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Biến thể không thuộc về sản phẩm này.',
            ], 422);
        }

        // Check stock
        if ($variant->stock_quantity <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Biến thể này hiện đã hết hàng trong kho.',
            ], 422);
        }

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        // Check if item already in cart
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        $priceToCharge = $variant->effective_price;
        $isBuyNow = $request->boolean('buy_now');

        if ($cartItem) {
            $newQuantity = $isBuyNow ? $quantity : ($cartItem->quantity + $quantity);
            if ($newQuantity > $variant->stock_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã có '.$cartItem->quantity.' sản phẩm trong giỏ. Tồn kho tối đa chỉ còn '.$variant->stock_quantity.'.',
                ], 422);
            }
            $cartItem->quantity = $newQuantity;
            $cartItem->price = $priceToCharge;
            $cartItem->save();
        } else {
            if ($quantity > $variant->stock_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số lượng yêu cầu ('.$quantity.') vượt quá tồn kho hiện có ('.$variant->stock_quantity.').',
                ], 422);
            }

            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'price' => $priceToCharge,
            ]);
        }

        $cart->refresh();
        $cart->load([
            'items.product.primaryImage',
            'items.variant.attributeValues.attribute',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm "'.$variant->product->name.' ('.$variant->variant_title.')" vào giỏ hàng!',
            'cart_item_id' => $cartItem->id,
            'count' => $cart->total_quantity,
            'total' => $cart->total_price,
            'items' => $cart->items,
        ]);
    }

    /**
     * Update cart item quantity
     */
    public function updateQuantity(Request $request, $id): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'need_login' => true,
                'message' => 'Vui lòng đăng nhập để thao tác giỏ hàng.',
            ], 401);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = Cart::where('user_id', Auth::id())->first();
        if (! $cart) {
            return response()->json(['success' => false, 'message' => 'Giỏ hàng không tồn tại.'], 404);
        }

        $cartItem = CartItem::where('cart_id', $cart->id)->where('id', $id)->first();
        if (! $cartItem) {
            return response()->json(['success' => false, 'message' => 'Sản phẩm không có trong giỏ.'], 404);
        }

        $variant = $cartItem->variant;
        $requestedQty = (int) $validated['quantity'];

        if ($variant && $requestedQty > $variant->stock_quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Số lượng vượt quá tồn kho hiện có ('.$variant->stock_quantity.').',
            ], 422);
        }

        $cartItem->quantity = $requestedQty;
        $cartItem->save();

        $cart->refresh();
        $cart->load([
            'items.product.primaryImage',
            'items.variant.attributeValues.attribute',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật số lượng!',
            'item_subtotal' => $cartItem->subtotal,
            'count' => $cart->total_quantity,
            'total' => $cart->total_price,
            'items' => $cart->items,
        ]);
    }

    /**
     * Remove item from cart
     */
    public function remove($id): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'need_login' => true,
                'message' => 'Vui lòng đăng nhập.',
            ], 401);
        }

        $cart = Cart::where('user_id', Auth::id())->first();
        if (! $cart) {
            return response()->json(['success' => false, 'message' => 'Giỏ hàng không tồn tại.'], 404);
        }

        $cartItem = CartItem::where('cart_id', $cart->id)->where('id', $id)->first();
        if ($cartItem) {
            $cartItem->delete();
        }

        $cart->refresh();
        $cart->load([
            'items.product.primaryImage',
            'items.variant.attributeValues.attribute',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng.',
            'count' => $cart->total_quantity,
            'total' => $cart->total_price,
            'items' => $cart->items,
        ]);
    }

    /**
     * Clear all items in cart
     */
    public function clear(): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['success' => false, 'need_login' => true], 401);
        }

        $cart = Cart::where('user_id', Auth::id())->first();
        if ($cart) {
            $cart->items()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã làm trống giỏ hàng.',
            'count' => 0,
            'total' => 0,
            'items' => [],
        ]);
    }
}
