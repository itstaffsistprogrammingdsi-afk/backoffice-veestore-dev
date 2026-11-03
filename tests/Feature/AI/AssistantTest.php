<?php

namespace Tests\Feature\AI;

use App\Ai\Agents\VeeAssistant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
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

    public function test_assistant_chat_validates_message(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');
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
