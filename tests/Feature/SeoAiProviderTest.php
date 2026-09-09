<?php

namespace Tests\Feature;

use App\Models\SeoAiSetting;
use App\Models\User;
use App\Services\SeoAi\OllamaSeoGenerator;
use App\Services\SeoAi\OpenAiSeoGenerator;
use App\Services\SeoAi\SeoGeneratorFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The local provider, and the two behaviours that decide whether it works at all.
 *
 * Everything is faked: these pin how we treat what a model returns, which is
 * where a small local model actually fails — not whether Ollama is up.
 */
class SeoAiProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    private function page(string $title): string
    {
        return json_encode([
            'slug' => 'lisbonne-restaurants',
            'title' => $title,
            'meta_description' => str_repeat('Description utile de Lisbonne. ', 4),
            'h1' => $title,
            'excerpt' => 'Une introduction courte.',
            'keywords' => ['lisbonne', 'restaurants'],
            'sections' => [['heading' => 'Où manger', 'paragraphs' => ['Un paragraphe.', 'Un autre.']]],
            'faq' => [['question' => 'Quand y aller ?', 'answer' => 'Toute l\'année.']],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * The trap that would have broken every single run.
     *
     * Qwen3 emits a <think> reasoning block before its answer by default. The
     * module's original parser only stripped Markdown fences, so json_decode
     * failed and the error read "invalid JSON" with no hint of the cause.
     */
    public function test_a_qwen_reasoning_block_does_not_break_the_parser(): void
    {
        Http::fake(['127.0.0.1:11434/api/generate' => Http::response([
            'response' => "<think>\nThe user wants JSON like {\"title\": \"...\"} with a sections array.\nLet me draft {\"slug\": \"x\"} first.\n</think>\n".$this->page('Restaurants à Lisbonne'),
        ])]);

        $result = app(OllamaSeoGenerator::class)->generate(['places' => []]);

        $this->assertArrayHasKey('fr', $result);
        $this->assertSame('Restaurants à Lisbonne', $result['fr']['title']);
    }

    /** A reasoning block cut off by the token limit never closes its tag. */
    public function test_an_unclosed_reasoning_block_is_also_handled(): void
    {
        Http::fake(['127.0.0.1:11434/api/generate' => Http::response([
            'response' => $this->page('Plages du Portugal')."\n<think> wait, should the shape be {\"h1\": \"other\"} instead",
        ])]);

        $result = app(OllamaSeoGenerator::class)->generate(['places' => []]);

        $this->assertSame('Plages du Portugal', $result['fr']['title']);
    }

    /** Small models like to add a closing sentence after the JSON. */
    public function test_trailing_prose_after_the_json_is_tolerated(): void
    {
        Http::fake(['127.0.0.1:11434/api/generate' => Http::response([
            'response' => "```json\n".$this->page('Bars à Porto')."\n```\nJ'espère que cela convient !",
        ])]);

        $result = app(OllamaSeoGenerator::class)->generate(['places' => []]);

        $this->assertSame('Bars à Porto', $result['fr']['title']);
    }

    /**
     * One locale per call. The combined request was twenty to forty minutes on
     * this hardware and lost everything on one bad brace.
     */
    public function test_it_asks_for_one_locale_at_a_time(): void
    {
        Http::fake(['127.0.0.1:11434/api/generate' => Http::response(['response' => $this->page('Titre')])]);

        app(OllamaSeoGenerator::class)->generate(['places' => []]);

        $locales = count(config('goodtriplove.locales'));
        Http::assertSentCount($locales);
    }

    /** A language that fails must not take the other five with it. */
    public function test_one_failing_locale_does_not_lose_the_page(): void
    {
        $calls = 0;
        Http::fake(['127.0.0.1:11434/api/generate' => function () use (&$calls) {
            $calls++;

            return $calls === 2
                ? Http::response('upstream exploded', 500)
                : Http::response(['response' => $this->page('Titre valide')]);
        }]);

        $result = app(OllamaSeoGenerator::class)->generate(['places' => []]);

        $this->assertCount(count(config('goodtriplove.locales')) - 1, $result);
    }

    /** Nothing usable at all is an error, not a silently empty page. */
    public function test_a_page_with_no_usable_locale_throws(): void
    {
        Http::fake(['127.0.0.1:11434/api/generate' => Http::response('nope', 500)]);

        $this->expectException(\RuntimeException::class);
        app(OllamaSeoGenerator::class)->generate(['places' => []]);
    }

    /**
     * Inference must not take every core: this box also serves the websites.
     */
    public function test_the_request_caps_threads_and_disables_thinking(): void
    {
        Http::fake(['127.0.0.1:11434/api/generate' => Http::response(['response' => $this->page('Titre')])]);

        app(OllamaSeoGenerator::class)->generate(['places' => []]);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['think'] === false
                && $body['format'] === 'json'
                && $body['options']['num_thread'] < 6;
        });
    }

    public function test_the_factory_follows_the_saved_provider(): void
    {
        $settings = SeoAiSetting::current();

        $settings->update(['provider' => 'openai']);
        $this->assertInstanceOf(OpenAiSeoGenerator::class, app(SeoGeneratorFactory::class)->make());

        $settings->update(['provider' => 'ollama']);
        $this->assertInstanceOf(OllamaSeoGenerator::class, app(SeoGeneratorFactory::class)->make());
    }

    /** An unknown provider must fall back to the free one, never the paid one. */
    public function test_an_unknown_provider_does_not_fall_through_to_the_paid_one(): void
    {
        SeoAiSetting::current()->update(['provider' => 'typo']);

        $this->assertInstanceOf(OllamaSeoGenerator::class, app(SeoGeneratorFactory::class)->make());
    }

    public function test_the_admin_screen_offers_both_providers_and_reports_ollama(): void
    {
        Http::fake(['127.0.0.1:11434/api/tags' => Http::response([
            'models' => [['name' => 'qwen3:4b']],
        ])]);

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN, 'is_active' => true,
            'two_factor_enabled' => true, 'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)->withSession(['2fa_passed_at' => now()->timestamp])
            ->get('/admin/seo-ai')
            ->assertOk()
            ->assertSee('Ollama', false)
            ->assertSee('OpenAI', false)
            ->assertSee('Service joignable', false);
    }

    /** Switching provider from the admin must actually persist. */
    public function test_the_provider_can_be_switched_from_the_admin(): void
    {
        Http::fake(['127.0.0.1:11434/api/tags' => Http::response(['models' => []])]);

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN, 'is_active' => true,
            'two_factor_enabled' => true, 'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)->withSession(['2fa_passed_at' => now()->timestamp])
            ->put('/admin/seo-ai/settings', [
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'ollama_url' => 'http://127.0.0.1:11434',
                'ollama_model' => 'qwen3:4b',
                'quality_threshold' => 72,
            ])
            ->assertRedirect();

        $this->assertSame('openai', SeoAiSetting::current()->fresh()->provider);
    }
}
