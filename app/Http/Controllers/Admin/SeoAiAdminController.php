<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoAiPage;
use App\Models\SeoAiSetting;
use App\Services\SeoAi\OllamaSeoGenerator;
use App\Services\SeoAi\SeoAiPublisher;
use App\Services\SeoAi\SeoGeneratorFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoAiAdminController extends Controller
{
    public function index(OllamaSeoGenerator $ollama): View
    {
        return view('admin.seo_ai.index', [
            'pages' => SeoAiPage::with('translations')->latest()->paginate(30),
            'settings' => SeoAiSetting::current(),
            'providers' => SeoGeneratorFactory::PROVIDERS,
            // "Nothing was generated" has two very different causes: the
            // service is down, or the model name is not pulled. Show which.
            'ollamaStatus' => $ollama->status(),
        ]);
    }

    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'], 'auto_publish' => ['nullable', 'boolean'],
            'provider' => ['required', 'in:'.implode(',', SeoGeneratorFactory::PROVIDERS)],
            'model' => ['required', 'string', 'max:120'],
            'ollama_url' => ['required', 'string', 'max:190'],
            'ollama_model' => ['required', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'min:20', 'max:500'],
            'quality_threshold' => ['required', 'integer', 'between:50,95'],
        ]);
        $settings = SeoAiSetting::current();
        $payload = [
            'enabled' => $request->boolean('enabled'), 'auto_publish' => $request->boolean('auto_publish'),
            'model' => $data['model'], 'quality_threshold' => $data['quality_threshold'],
            'provider' => $data['provider'],
            'ollama_url' => $data['ollama_url'], 'ollama_model' => $data['ollama_model'],
        ];
        if (filled($data['api_key'] ?? null)) $payload['api_key'] = $data['api_key'];
        $settings->update($payload);
        return back()->with('status', 'SEO AI settings saved.');
    }

    public function generate(SeoAiPublisher $publisher): RedirectResponse
    {
        $page = $publisher->generateNext(true);
        return back()->with($page ? 'status' : 'warning', $page ? "Page #{$page->id} generated for review." : 'No eligible topic found.');
    }

    public function publish(SeoAiPage $page): RedirectResponse
    {
        if ($page->translations()->count() === 0) return back()->with('warning', 'No translations to publish.');
        $page->update(['status' => SeoAiPage::STATUS_PUBLISHED, 'indexable' => true, 'published_at' => now()]);
        return back()->with('status', 'Page published.');
    }

    public function unpublish(SeoAiPage $page): RedirectResponse
    {
        $page->update(['status' => SeoAiPage::STATUS_REVIEW, 'indexable' => false, 'published_at' => null]);
        return back()->with('status', 'Page unpublished.');
    }
}
