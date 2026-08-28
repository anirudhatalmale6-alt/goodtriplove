<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\Announcement;
use Illuminate\Support\Collection;

/**
 * Simple Ads Manager: the advertising spaces between categories, the temporary
 * promotional banners and the scrolling announcement line.
 */
class AdService
{
    /**
     * @return Collection<int, Ad>
     */
    public function forPlacement(string $placement, array $context = [], int $limit = 3): Collection
    {
        return Ad::query()
            ->live()
            ->forLocale(app()->getLocale())
            ->where('placement', $placement)
            ->when($context['country_id'] ?? null, fn ($q, $id) => $q->where(
                fn ($sub) => $sub->whereNull('country_id')->orWhere('country_id', $id)
            ))
            ->when($context['city_id'] ?? null, fn ($q, $id) => $q->where(
                fn ($sub) => $sub->whereNull('city_id')->orWhere('city_id', $id)
            ))
            ->when($context['category_id'] ?? null, fn ($q, $id) => $q->where(
                fn ($sub) => $sub->whereNull('category_id')->orWhere('category_id', $id)
            ))
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    public function first(string $placement, array $context = []): ?Ad
    {
        return $this->forPlacement($placement, $context, 1)->first();
    }

    /**
     * The ticker. Returns the localized strings only, so the layout never has
     * to reason about the model.
     *
     * @return array<int, array{text: string, url: ?string, emoji: ?string}>
     */
    public function ticker(): array
    {
        return $this->announcements(Announcement::PLACEMENT_TICKER);
    }

    /**
     * Live announcements for one position, in the order the admin set.
     *
     * `home_only` rows are dropped everywhere except the home page, which is
     * what makes a seasonal banner possible without it following the visitor
     * onto every video page.
     */
    public function announcements(string $placement): array
    {
        return Announcement::live()
            ->where('placement', $placement)
            ->when(! request()->routeIs('home'), fn ($q) => $q->where('home_only', false))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Announcement $a) => [
                'text' => $a->body(),
                'url' => $a->url,
                'emoji' => $a->emoji,
            ])
            ->filter(fn (array $item) => filled($item['text']))
            ->values()
            ->all();
    }

    public function recordImpressions(Collection $ads): void
    {
        if ($ads->isEmpty()) {
            return;
        }

        Ad::whereKey($ads->pluck('id'))->increment('impressions');
    }
}
