# GoodTripLove SEO AI Manager — Design

## Goal
Add a self-contained SEO AI subsystem to the existing Laravel 13 GoodTripLove application. It must generate high-quality multilingual landing/editorial pages around countries, cities, categories and subcategories, publish at most one new page automatically per week, and avoid thin/duplicate pages.

## Existing integration points
- Laravel 13 / PHP 8.3+ / MariaDB.
- Six locales already configured in `config('goodtriplove.locales')`.
- Existing taxonomy: countries, cities, categories/subcategories, places and videos.
- Existing admin is protected by auth, role and 2FA.
- Existing scheduler runs from `routes/console.php` with database queues and no Redis/Supervisor.
- Existing site layout already renders title, description, canonical, robots, hreflang and structured data.

## Architecture
The module adds two persistent entities: `SeoAiPage` (topic, targeting, lifecycle and quality) and `SeoAiPageTranslation` (localized SEO/editorial content). `SeoAiPlanner` chooses a weekly topic from live catalogue data; `SeoAiGenerator` calls the OpenAI Responses API and validates structured JSON; `SeoAiQualityService` scores coverage, uniqueness and catalogue support before publication. A scheduled command generates one candidate each week and only auto-publishes it if it clears a configurable score threshold.

## Weekly generation rule
- Default cadence: Monday 04:45 server time.
- Exactly one page candidate per weekly run.
- Topic rotation favors city+category, country+category, city guide and category guide pages.
- A topic is not reused for 52 weeks unless its prior page is rejected/deleted.
- A page is generated only when sufficient supporting catalogue data exists.
- If quality is below threshold, save as `review`; do not publish or index.

## Public pages
Route: `/{locale}/discover/{slug}`. Every published translation provides unique title, meta description, H1, editorial body, FAQ, canonical, hreflang alternatives and JSON-LD (`Article`, `BreadcrumbList`, optional `FAQPage`). The page includes internal links to matching countries/cities/categories/places and approved videos.

## Admin
`/admin/seo-ai` shows pages, quality score, language coverage, status and generation date. Actions: generate now, publish/unpublish, regenerate translations and edit core settings. API key is never displayed back after save; it is encrypted at rest.

## AI
Primary provider: OpenAI Responses API over Laravel HTTP client, with structured JSON requested from the model. The module does not add an SDK dependency. Required configuration: API key and model name. The generated prompt includes only data already present in GoodTripLove plus strict instructions not to invent businesses, ratings, prices or facts.

## SEO safety
No automatic combinatorial page explosion. Pages require catalogue evidence and minimum content thresholds. Duplicate topic keys are unique. Quality checks reject generic output, short body content, duplicated titles and pages without internal-link targets. Draft/review pages are noindex because they are not publicly routable.

## Failure handling
Network/API failures are logged and the weekly command exits without publishing. Invalid JSON is rejected and stored as a generation failure. All writes are transactional. A failed week can be retried manually from admin.

## Testing
Feature tests cover route visibility, no access to drafts, weekly uniqueness, low-quality non-publication and admin generate action. Unit tests cover quality scoring and topic selection. PHP syntax is linted for every shipped file.
