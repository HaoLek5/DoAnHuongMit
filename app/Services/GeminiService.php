<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected ?string $apiKey;

    protected string $model;

    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-3.5-flash-lite');
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    /**
     * Check if Gemini API is configured.
     */
    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Generate product consultation response using Gemini AI.
     * The AI is ONLY provided with public product tables data (products, categories, variants).
     * No sensitive tables (users, orders, sessions, reports) are ever exposed.
     */
    public function generateConsultation(string $customerMessage, array $publicProducts): ?array
    {
        if (! $this->isAvailable()) {
            return null;
        }

        try {
            $systemInstruction = <<<'TEXT'
Bạn là trợ lý ảo tư vấn sản phẩm của cửa hàng Mẹ & Bé "Hương Mít".

DỮ LIỆU CỦA BẠN:
Bạn CHỈ ĐƯỢC CẤP QUYỀN ĐỌC duy nhất danh sách các sản phẩm đang bán tại cửa hàng Hương Mít (được cung cấp dưới định dạng JSON). Bạn TUYỆT ĐỐI KHÔNG CÓ QUYỀN TRUY CẬP vào bất kỳ dữ liệu nào khác (như thông tin khách hàng, đơn hàng, tài khoản, doanh thu...).

QUY TẮC BẮT BUỘC:
1. CHỈ TƯ VẤN SẢN PHẨM TRONG DANH SÁCH: Bạn chỉ tư vấn, giải đáp thắc mắc và gợi ý các sản phẩm có trong danh sách sản phẩm được cung cấp. Tuyệt đối không tự bịa ra sản phẩm cửa hàng không bán.
2. TỪ CHỐI CÂU HỎI NGOÀI LỀ: Bạn tuyệt đối từ chối trả lời các câu hỏi không liên quan đến mua sắm và sản phẩm mẹ & bé tại cửa hàng (như giải toán, viết code lập trình, hỏi thời tiết, chính trị, địa lý, dịch thuật, chuyện phiếm...). Khi từ chối, hãy xưng "em" và lịch sự nói: "Dạ em là trợ lý tư vấn sản phẩm của Hương Mít nên chỉ hỗ trợ các câu hỏi về sản phẩm cho mẹ và bé thôi ạ! Ba mẹ cần tìm sữa, tã hay đồ dùng gì cứ nhắn em nhé. 🍼"
3. HIỂU TIẾNG VIỆT TỰ NHIÊN: Tự động thấu hiểu các từ viết tắt, từ lóng hoặc lỗi chính tả thông thường của khách hàng (ví dụ: "khoog" = "không", "ko", "k", "dc", "sp", "me" = "mẹ", "be" = "bé", v.v.) mà không cần sửa lưng hay bắt bẻ khách.
4. GIỌNG ĐIỆU: Thân thiện, ngọt ngào, xưng "em" và gọi khách là "ba mẹ" hoặc "bạn", dùng emoji ấm áp (🍼, ✨, ❤️).
5. TRẢ VỀ ID SẢN PHẨM: Ở cuối câu trả lời, hãy kèm theo một dòng định dạng:
[RECOMMENDED_IDS: id1, id2] (Ví dụ: [RECOMMENDED_IDS: 7] hoặc [RECOMMENDED_IDS: 1, 2]) tương ứng với các sản phẩm bạn gợi ý trong câu trả lời để hệ thống hiển thị thẻ sản phẩm cho khách. Nếu là lời chào chung hoặc câu hỏi ngoài lề bị từ chối, ghi: [RECOMMENDED_IDS: none].
TEXT;

            $userPrompt = "Danh sách sản phẩm cửa hàng Hương Mít đang bán:\n".json_encode($publicProducts, JSON_UNESCAPED_UNICODE)."\n\nKhách hỏi: \"{$customerMessage}\"";

            $payload = [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemInstruction],
                    ],
                ],
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $userPrompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 600,
                ],
            ];

            // Truyền API key qua Header để tránh lộ bí mật qua URL/server logs; bật xác minh SSL ở môi trường staging/production
            $configuredVerifySsl = config('services.gemini.verify_ssl');
            $verifySsl = $configuredVerifySsl === null
                ? app()->environment(['production', 'staging'])
                : filter_var($configuredVerifySsl, FILTER_VALIDATE_BOOLEAN);

            $response = Http::withHeaders([
                'x-goog-api-key' => (string) $this->apiKey,
            ])->withOptions([
                'force_ip_resolve' => 'v4',
                'verify' => $verifySsl,
            ])->timeout(12)->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $rawReply = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';

                $recommendedIds = [];
                $isOutOfScope = false;

                if (preg_match('/\[RECOMMENDED_IDS:\s*([^\]]+)\]/', $rawReply, $m)) {
                    $idsPart = trim($m[1]);
                    if ($idsPart !== 'none' && $idsPart !== '') {
                        $recommendedIds = array_values(array_filter(array_map('intval', explode(',', $idsPart))));
                    } else {
                        // Check if the reply indicates an off-topic refusal
                        if (str_contains($rawReply, 'chỉ hỗ trợ các câu hỏi về sản phẩm') ||
                            str_contains($rawReply, 'ngoài lề') ||
                            str_contains($rawReply, 'không có quyền') ||
                            str_contains($rawReply, 'chỉ có thể hỗ trợ')) {
                            $isOutOfScope = true;
                        }
                    }
                    $cleanReply = trim(str_replace($m[0], '', $rawReply));
                } else {
                    $cleanReply = trim($rawReply);
                    if (str_contains($rawReply, 'chỉ hỗ trợ các câu hỏi về sản phẩm') ||
                        str_contains($rawReply, 'ngoài lề') ||
                        str_contains($rawReply, 'không có quyền')) {
                        $isOutOfScope = true;
                    }
                }

                return [
                    'reply' => $cleanReply,
                    'recommended_ids' => $recommendedIds,
                    'is_out_of_scope' => $isOutOfScope,
                ];
            } else {
                Log::warning('Gemini API returned error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Gemini API exception: '.$e->getMessage());
        }

        return null;
    }
}
