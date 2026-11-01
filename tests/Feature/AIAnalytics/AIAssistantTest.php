<?php

namespace Tests\Feature\AIAnalytics;

use App\Ai\Agents\SalesAnalyst;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class AIAssistantTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_assistant_page_can_be_rendered(): void
    {
        $this->actingAs($this->admin())
            ->get(route('ai-assistant.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Assistant/Index')
                ->has('suggestions')
                ->has('urls'));
    }

    public function test_assistant_answers_with_the_sales_analyst_agent(): void
    {
        SalesAnalyst::fake(['Risiko kritis ada di toko contoh.']);

        $this->actingAs($this->admin())
            ->post(route('ai-assistant.ask'), [
                'prompt' => 'Toko mana yang paling berisiko?',
            ])
            ->assertRedirect(route('ai-assistant.index'))
            ->assertSessionHas('answer', 'Risiko kritis ada di toko contoh.');

        SalesAnalyst::assertPrompted('Toko mana yang paling berisiko?');
    }

    public function test_assistant_requires_ai_analytics_permission(): void
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
