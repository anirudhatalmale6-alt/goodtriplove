<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoOverride;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Per-page, per-language SEO metadata.
 *
 * The storage and the lookup service already existed; nothing rendered them.
 * SeoComposer now feeds the layout, so what is saved here genuinely changes the
 * <title>, the description, the canonical link and the robots directive.
 */
class SeoAdminController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): View
    {
        $locale = $request->string('locale')->toString() ?: config('goodtriplove.default_locale');

        return view('admin.seo.index', [
            'overrides' => SeoOverride::orderBy('page_type')->orderBy('page_key')->orderBy('locale')->paginate(40),
            'locales' => array_keys(config('goodtriplove.locales')),
            'locale' => $locale,
            'pages' => $this->publicPages(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        // One row per page and language: saving the same page twice must edit
        // it, not create a second row that silently loses to the first.
        $override = SeoOverride::updateOrCreate(
            [
                'page_type' => $data['page_type'],
                'page_key' => $data['page_key'],
                'locale' => $data['locale'],
            ],
            $data
        );

        $this->audit->record('seo.save', $override, [], $data);

        return back()->with('status', __('gtl.saved'));
    }

    public function update(Request $request, SeoOverride $seo): RedirectResponse
    {
        $data = $this->validated($request);
        $old = $seo->only(array_keys($data));

        $seo->update($data);
        $this->audit->record('seo.update', $seo, $old, $data);

        return back()->with('status', __('gtl.saved'));
    }

    public function destroy(SeoOverride $seo): RedirectResponse
    {
        $this->audit->record('seo.delete', $seo, $seo->toArray(), []);
        $seo->delete();

        return back()->with('status', __('gtl.deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'page_type' => ['required', 'string', 'max:64'],
            'page_key' => ['required', 'string', 'max:191'],
            'locale' => ['required', Rule::in(array_keys(config('goodtriplove.locales')))],
            'title' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'indexable' => ['nullable', 'boolean'],
        ]) + ['indexable' => $request->boolean('indexable', true)];
    }

    /**
     * The public route names an override can target, so the administrator picks
     * from a list instead of guessing a string that would silently never match.
     */
    private function publicPages(): array
    {
        $names = [];

        foreach (RouteFacade::getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || str_starts_with($name, 'admin.') || ! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $names[$name] = $name.(str_contains($route->uri(), '{') ? '  (needs a page key)' : '');
        }

        ksort($names);

        return $names;
    }
}
