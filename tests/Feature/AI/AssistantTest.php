<?php

namespace Tests\Feature\AI;

use App\Ai\Agents\VeeAssistant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_assistant_page_can_be_rendered(): void
    {
        $this->actingAs($this->admin())
            ->get(route('ai-assistant.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assistant/Index')
                ->has('messages')
                ->has('suggestions')
                ->has('tools')
                ->has('features')
                ->has('coverage')
                ->has('modes')
                ->has('default_mode')
                ->has('urls.chat')
                ->has('urls.reset')
                ->has('urls.mcp'));
    }

    public function test_assistant_chat_returns_answer_and_keeps_history(): void
    {
        VeeAssistant::fake(['Omzet bulan ini tumbuh dari data penjualan.']);

        $response = $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), [
                'message' => 'Bagaimana omzet bulan ini?',
            ])
            ->assertOk()
            ->assertJsonPath('answer', 'Omzet bulan ini tumbuh dari data penjualan.')
            ->assertJsonCount(2, 'messages');

        $this->assertSame('user', $response->json('messages.0.role'));
        $this->assertSame('assistant', $response->json('messages.1.role'));

        VeeAssistant::assertPrompted('Bagaimana omzet bulan ini?');
    }

    public function test_assistant_modes_use_codex_with_opencode_fallbacks(): void
    {
        $models = (array) config('ai.providers.nine_router.models.text');

        $this->assertSame('cx/gpt-5.6-luna', $models['auto']);
        $this->assertSame('cx/gpt-5.6-sol', $models['auto_secondary']);
        $this->assertSame('ocg/deepseek-v4.1-flash', $models['auto_fallback']);
        $this->assertSame('ocg/glm-5.3-flash', $models['auto_fallback_secondary']);
        $this->assertSame('cx/gpt-6.1-sol', $models['expert']);
        $this->assertSame('ocg/claude-haiku-5-5', $models['expert_fallback']);

        $this->assertSame(
            ['auto', 'auto_secondary', 'auto_fallback', 'auto_fallback_secondary'],
            config('assistant.modes.auto.models')
        );
        $this->assertSame(['expert', 'expert_fallback'], config('assistant.modes.expert.models'));
    }

    public function test_assistant_allows_enough_steps_for_free_form_questions(): void
    {
        $this->assertGreaterThanOrEqual(8, (new VeeAssistant)->maxSteps());
    }

    public function test_assistant_chat_uses_expert_mode_model(): void
    {
        VeeAssistant::fake(['Analisis expert.']);

        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), [
                'message' => 'Analisis stok mendalam.',
                'mode' => 'expert',
            ])
            ->assertOk()
            ->assertJsonPath('mode', 'expert')
            ->assertJsonPath('model', config('ai.providers.nine_router.models.text.expert'));
    }

    public function test_assistant_auto_mode_falls_back_to_secondary_model(): void
    {
        $attempts = 0;
        $seenModels = [];

        VeeAssistant::fake(function () use (&$attempts, &$seenModels): string {
            $attempts++;
            $seenModels[] = func_get_arg(3);

            if ($attempts === 1) {
                throw new RuntimeException('Model utama tidak tersedia.');
            }

            return 'Jawaban dari model cadangan.';
        });

        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Pertanyaan.'])
            ->assertOk()
            ->assertJsonPath('mode', 'auto')
            ->assertJsonPath('model', config('ai.providers.nine_router.models.text.auto_secondary'));

        $this->assertSame([
            config('ai.providers.nine_router.models.text.auto'),
            config('ai.providers.nine_router.models.text.auto_secondary'),
        ], $seenModels);
    }

    public function test_assistant_auto_mode_uses_all_fallbacks_in_order(): void
    {
        $attempts = 0;
        $seenModels = [];

        VeeAssistant::fake(function () use (&$attempts, &$seenModels): string {
            $attempts++;
            $seenModels[] = func_get_arg(3);

            if ($attempts < 4) {
                throw new RuntimeException('Model tidak tersedia.');
            }

            return 'Jawaban dari cadangan terakhir.';
        });

        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Pertanyaan.'])
            ->assertOk()
            ->assertJsonPath('mode', 'auto')
            ->assertJsonPath('model', config('ai.providers.nine_router.models.text.auto_fallback_secondary'));

        $this->assertSame([
            config('ai.providers.nine_router.models.text.auto'),
            config('ai.providers.nine_router.models.text.auto_secondary'),
            config('ai.providers.nine_router.models.text.auto_fallback'),
            config('ai.providers.nine_router.models.text.auto_fallback_secondary'),
        ], $seenModels);
    }

    public function test_assistant_expert_mode_falls_back_to_haiku(): void
    {
        $attempts = 0;
        $seenModels = [];

        VeeAssistant::fake(function () use (&$attempts, &$seenModels): string {
            $attempts++;
            $seenModels[] = func_get_arg(3);

            if ($attempts === 1) {
                throw new RuntimeException('Model expert tidak tersedia.');
            }

            return 'Jawaban dari model cadangan expert.';
        });

        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Analisis.', 'mode' => 'expert'])
            ->assertOk()
            ->assertJsonPath('mode', 'expert')
            ->assertJsonPath('model', config('ai.providers.nine_router.models.text.expert_fallback'));

        $this->assertSame([
            config('ai.providers.nine_router.models.text.expert'),
            config('ai.providers.nine_router.models.text.expert_fallback'),
        ], $seenModels);
    }

    public function test_assistant_returns_fallback_when_model_returns_empty_answer(): void
    {
        VeeAssistant::fake(['']);

        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Pertanyaan bebas.'])
            ->assertOk()
            ->assertJsonPath(
                'answer',
                'VEE belum bisa menyusun jawaban untuk pertanyaan ini. Coba tuliskan pertanyaan dengan periode, nama produk, atau nama toko yang lebih spesifik.'
            );
    }

    public function test_assistant_chat_validates_message(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
    }

    public function test_assistant_chat_validates_mode(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Pertanyaan.', 'mode' => 'unknown'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mode');
    }

    public function test_assistant_state_returns_session_messages(): void
    {
        VeeAssistant::fake(['Jawaban pertama.']);

        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Pertanyaan pertama.'])
            ->assertOk();

        $this->actingAs($this->admin())
            ->getJson(route('ai-assistant.state'))
            ->assertOk()
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.content', 'Pertanyaan pertama.');
    }

    public function test_assistant_reset_clears_history(): void
    {
        VeeAssistant::fake(['Jawaban.']);

        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Pertanyaan.'])
            ->assertOk();

        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.reset'))
            ->assertOk()
            ->assertJsonPath('messages', []);

        $this->actingAs($this->admin())
            ->getJson(route('ai-assistant.state'))
            ->assertOk()
            ->assertJsonCount(0, 'messages');
    }

    public function test_assistant_requires_permission(): void
    {
        $user = User::query()->create([
            'name' => 'No Role User',
            'username' => 'no-role-'.Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->get(route('ai-assistant.index'))
            ->assertForbidden();
    }
}
