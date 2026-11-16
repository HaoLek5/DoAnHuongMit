<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ChatbotService
{
    public function __construct(
        protected ?GeminiService $geminiService = null
    ) {
        $this->geminiService = $geminiService ?? app(GeminiService::class);
    }

    /**
     * Process an incoming message from a customer.
     */
    public function processMessage(string $rawMessage): array
    {
        $message = trim($rawMessage);
        if ($message === '') {
            return [
                'status' => 'error',
                'is_out_of_scope' => false,
                'reply' => 'Bạn hãy nhập yêu cầu để em tư vấn sản phẩm phù hợp cho bé nhé!',
                'products' => [],
                'suggested_chips' => [],
            ];
        }

        // Fast greeting detection
        if ($this->isGreeting($message)) {
            $categories = Category::pluck('name')->implode(', ');

            return [
                'status' => 'success',
                'is_out_of_scope' => false,
                'reply' => "Dạ Hương Mít xin chào bạn! 🍼✨\n\nEm là trợ lý ảo chuyên tư vấn sản phẩm cho Mẹ & Bé. Shop hiện đang có sẵn các mặt hàng chất lượng cao gồm: {$categories}.\n\nBạn đang cần tìm sản phẩm gì cho bé yêu hoặc mẹ (độ tuổi, cân nặng, tình trạng tiêu hóa, đồ mặc nhà...) để em gợi ý phù hợp nhất ạ?",
                'products' => [],
                'suggested_chips' => [],
            ];
        }

        // 1. Get public product catalog ONLY from product tables (products, categories, variants)
        // No user, order, or sensitive table is ever accessed.
        $publicCatalog = $this->getPublicProductsCatalog();

        // 2. Delegate understanding & consultation directly to Gemini AI
        if ($this->geminiService && $this->geminiService->isAvailable()) {
            $aiResult = $this->geminiService->generateConsultation($message, $publicCatalog);
            if ($aiResult !== null) {
                $recommendedProducts = [];
                if (! empty($aiResult['recommended_ids'])) {
                    $productsCollection = Product::where('status', true)
                        ->whereIn('id', $aiResult['recommended_ids'])
                        ->with(['category', 'primaryImage', 'variants.attributeValues.attribute', 'variants.activeSaleItems.campaign'])
                        ->get();
                    $recommendedProducts = $this->formatProductsForChat($productsCollection);
                }

                return [
                    'status' => 'success',
                    'is_out_of_scope' => $aiResult['is_out_of_scope'],
                    'reply' => $aiResult['reply'],
                    'products' => $recommendedProducts,
                    'suggested_chips' => [],
                ];
            }
        }

        // 3. Fallback when AI is unavailable: simple product search in product table
        return $this->fallbackProductSearch($message);
    }

    /**
     * Retrieve public product catalog ONLY from product tables.
     * Restricts AI knowledge strictly to active store items.
     */
    public function getPublicProductsCatalog(): array
    {
        return Product::where('status', true)
            ->with(['category', 'variants.attributeValues.attribute', 'variants.activeSaleItems.campaign'])
            ->get()
            ->map(function ($p) {
                $variantSummary = $p->variants->map(function ($v) {
                    return $v->attributeValues->pluck('value')->implode(' ');
                })->filter()->implode(', ');

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'category' => $p->category->name ?? 'Đồ Mẹ & Bé',
                    'price' => number_format($p->base_price, 0, ',', '.').' đ',
                    'stock' => $p->total_stock,
                    'variants' => $variantSummary ?: 'Tiêu chuẩn',
                    'description' => $p->description,
                ];
            })->values()->toArray();
    }

    /**
     * Check if a message is a simple greeting.
     */
    protected function isGreeting(string $message): bool
    {
        $clean = mb_strtolower(trim($message), 'UTF-8');
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $clean);

        $greetings = [
            'chào', 'chao', 'xin chào', 'xin chao', 'hello', 'hi', 'alo',
            'shop ơi', 'shop oi', 'ad ơi', 'ad oi', 'có ai không', 'co ai khong',
            'chào shop', 'chao shop', 'hello shop', 'hi shop',
        ];

        return in_array($clean, $greetings);
    }

    /**
     * Fallback search directly in product table if AI is unavailable.
     */
    protected function fallbackProductSearch(string $message): array
    {
        $clean = mb_strtolower(trim($message), 'UTF-8');
        $clean = preg_replace('/[^\p{L}\p{N}\s]/u', '', $clean);
        $words = array_filter(explode(' ', $clean), fn ($w) => mb_strlen($w) >= 2);

        $query = Product::where('status', true)
            ->with(['category', 'primaryImage', 'variants.attributeValues.attribute', 'variants.activeSaleItems.campaign']);

        if (! empty($words)) {
            $query->where(function ($q) use ($words) {
                foreach ($words as $w) {
                    $q->orWhere('name', 'like', "%{$w}%")
                        ->orWhere('description', 'like', "%{$w}%");
                }
            });
        }

        $products = $query->orderByDesc('id')->take(3)->get();
        if ($products->isEmpty()) {
            $products = Product::where('status', true)
                ->with(['category', 'primaryImage', 'variants.attributeValues.attribute', 'variants.activeSaleItems.campaign'])
                ->orderByDesc('id')
                ->take(3)
                ->get();
        }

        return [
            'status' => 'success',
            'is_out_of_scope' => false,
            'reply' => 'Dạ Hương Mít xin gợi ý một số sản phẩm phù hợp đang có sẵn tại cửa hàng để ba mẹ tham khảo nhé:',
            'products' => $this->formatProductsForChat($products),
            'suggested_chips' => [],
        ];
    }

    /**
     * Format product models for the chatbot UI cards.
     */
    protected function formatProductsForChat(Collection $products): array
    {
        return $products->map(function ($p) {
            $variantSummary = $p->variants->map(function ($v) {
                return $v->attributeValues->pluck('value')->implode(' ');
            })->filter()->implode(', ');

            return [
                'id' => $p->id,
                'name' => $p->name,
                'category_name' => $p->category->name ?? 'Đồ Mẹ & Bé',
                'base_price' => (float) $p->base_price,
                'formatted_price' => number_format($p->base_price, 0, ',', '.').' đ',
                'total_stock' => $p->total_stock,
                'image_url' => $p->primary_image_url ?? asset('images/product-placeholder.png'),
                'description' => Str::limit($p->description, 120),
                'variants_summary' => $variantSummary ?: 'Tiêu chuẩn',
                'url' => route('home').'#san-pham-'.$p->id,
            ];
        })->values()->toArray();
    }

    /**
     * Default quick prompt chips (kept for backward compatibility).
     */
    public function getDefaultChips(): array
    {
        return [];
    }
}
