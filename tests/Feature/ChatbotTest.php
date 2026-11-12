<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test chatbot rejects off-topic queries.
     */
    public function test_chatbot_rejects_off_topic_queries(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "Dạ em là trợ lý tư vấn sản phẩm của Hương Mít nên chỉ hỗ trợ các câu hỏi về sản phẩm cho mẹ và bé thôi ạ! Ba mẹ cần tìm sữa, tã hay đồ dùng gì cứ nhắn em nhé. 🍼\n[RECOMMENDED_IDS: none]"],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $offTopicQuestions = [
            'Thời tiết hôm nay thế nào?',
            'Viết giúp tôi một đoạn code python tính giai thừa',
            '1 + 1 bằng mấy?',
            'Tổng thống Mỹ là ai?',
            'Thủ đô của nước Pháp là gì?',
            'Hôm nay trời có mưa không?',
        ];

        foreach ($offTopicQuestions as $q) {
            $response = $this->postJson('/chatbot/message', [
                'message' => $q,
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'is_out_of_scope' => true,
                    'products' => [],
                ]);
        }
    }

    /**
     * Test chatbot welcomes on greetings.
     */
    public function test_chatbot_responds_to_greetings(): void
    {
        $response = $this->postJson('/chatbot/message', [
            'message' => 'Xin chào shop',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_out_of_scope' => false,
            ]);
    }

    /**
     * Test chatbot returns prompt suggestions.
     */
    public function test_chatbot_returns_suggestions(): void
    {
        $response = $this->getJson('/chatbot/suggestions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'chips',
            ]);
    }

    /**
     * Test chatbot integration with Gemini AI service.
     */
    public function test_chatbot_generates_ai_response_with_gemini(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Dạ em chào ba mẹ! Em xin gợi ý sữa bột Friso Gold Pro cho bé 1 tuổi ạ.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/chatbot/message', [
            'message' => 'Tư vấn giúp mình sữa cho bé 1 tuổi',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_out_of_scope' => false,
            ])
            ->assertJsonPath('reply', 'Dạ em chào ba mẹ! Em xin gợi ý sữa bột Friso Gold Pro cho bé 1 tuổi ạ.');
    }

    /**
     * Test chatbot properly handles queries for mothers and clothes.
     */
    public function test_chatbot_accepts_mother_and_pajama_queries(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "Dạ cửa hàng em có mẫu pijama cotton rất thoải mái và xinh xắn dành cho mẹ đấy ạ!\n[RECOMMENDED_IDS: 7]"],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/chatbot/message', [
            'message' => 'có bộ nào cho mẹ khoog?',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'is_out_of_scope' => false,
            ]);
    }
}
