# GoodTripLove

Video-first travel discovery: restaurants, local food, hotels, guest houses,
bars and cafés, activities, places to visit and beaches — surfaced through the
best public videos about each place, in six languages.

Laravel 13 · PHP 8.3 · MariaDB · no build step (the production server has no
Node, so CSS and JS are served as-is from `public/`).

---

## What is built

### Discovery
- Countries → cities → categories → places, all editable from the admin.
- Every place and every context exposes the five ranked sections from the
  brief: **most viewed · most popular · trending · most relevant · recent**.
- **GoodTripLove TV** — a continuous playlist that follows the visitor's
  country, city and category (Portugal → Porto → Restaurants) and widens
  automatically when the catalogue is still thin, so the player is never empty.
- Search with filters and type-ahead suggestions, favourites, business area.

### Video handling — embed only
- Videos are found through the **official YouTube Data API v3** and played in
  YouTube's own player. Nothing is downloaded, copied or re-hosted.
- **Facade player**: a page shows thumbnails only. No `<iframe>` exists until
  the visitor clicks — a homepage with 40 videos loads zero players.
- No third-party player is created before cookie consent has been given.
  Clicking play without consent shows the choice in place.

### Video collector
- Saved searches per country / city / category / language, run on a schedule.
- **Metered quota**: the free tier is 10 000 units/day and one search costs
  100, so the collector stops at a configurable hard-stop percentage rather
  than letting Google refuse requests for the rest of the day.
- Metrics are refreshed with `videos.list` (1 unit per 50 ids), which also
  detects videos that were deleted, made private or had embedding disabled.
- **Classification**: a deterministic text pass first (country, city, category,
  language), then the local model only where that pass was unsure. If Ollama is
  down the collector keeps working on the text pass alone.
- **Place matching** refuses to attach a video unless the place name actually
  appears in it — "a restaurant in Porto" must not attach itself to every
  restaurant in Porto. Weak matches wait for moderation.

### Ranking
`popularity` blends reach (log-scaled views), engagement and freshness, so an
old 3 M-view video does not permanently outrank a strong recent one.
`trending` is a measured delta between two metric snapshots — a video with only
one snapshot scores zero rather than guessing.

### Accounts and security
- Registration → Cloudflare Turnstile → rate limit → **6-digit email code**.
- Login throttling, temporary IP/account blocks, security log.
- **Mandatory 2FA (TOTP)** for admin and super admin, with server-rendered QR
  enrolment — the secret never leaves the server.
- Free business registration; every listing and every video is published only
  after an administrator approves it.

### Legal and compliance
- Versioned legal documents per language, edited from the admin; acceptance and
  consent records keep pointing at the exact version accepted.
- Cookie banner with Accept / Reject / Customize, a permanent "manage cookies"
  link, and consent enforced before any third-party embed.
- Content reporting on every video with a reference number, a documented
  decision cycle and an audit trail.

### Administration
Dashboard · videos (moderation, bulk actions, place linking) · places ·
countries / cities / categories · video collector · ads and scrolling
announcements · users · settings and app releases · Security Center ·
Growth & Ops · data quality · reports · legal texts · service status ·
YouTube quota · feature flags · error centre.

---

## Local setup

```
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan gtl:admin you@example.com          # creates the first admin
php artisan serve
```

`php artisan gtl:demo-content` fills the site with clearly-labelled placeholder
content for design review; `--purge` removes every trace of it.

## Scheduled work

One cron entry drives everything (see `routes/console.php`):

```
* * * * * cd /path/to/goodtriplove && php artisan schedule:run >> /dev/null 2>&1
```

The queue runs from the scheduler (`queue:work --stop-when-empty`) because the
target server has neither Redis nor Supervisor.

## Commands

| Command | What it does |
| --- | --- |
| `gtl:collect` | Runs the due collector searches |
| `gtl:refresh-videos` | Refreshes metrics and availability |
| `gtl:classify` | Classification. `--rescan` re-examines everything, not only unresolved rows |
| `gtl:prune-videos` | Re-judges stored videos against the relevance gate. Dry run unless `--force` |
| `gtl:rescore` | Recomputes popularity / trending / quality |
| `gtl:admin {email}` | Creates or promotes an administrator |
| `gtl:demo-content` | Placeholder content for design review |

## Why the collector rejects results

YouTube's search is associative, not literal, so a query is a hint rather than a
filter. Searching `melhores bares Madeira` returns carpentry, paint and decking
videos, because *madeira* is Portuguese for **wood**; a restaurants query
returned *Restaurant Tycoon 3*. On the first live run, 39% of everything
imported had nothing to do with travel.

A result is therefore only stored if its text **names a place the site covers**
*and* either matches a category or reads as a travel video (`cidade`, `ilha`,
`itinéraire`, `things to do`… in all six languages). Rejections are counted on
the run and a few examples are recorded in its message, so a query that discards
20 of 25 results is visible rather than looking like a query that found 5.

The gate is a heuristic and will occasionally be wrong in both directions, which
is why nothing reaches the public site without administrator approval.
`tests/Feature/VideoRelevanceTest.php` pins its behaviour against the real
titles — good and bad — that shaped it; run it before changing the rules.

## Still to configure

- `YOUTUBE_API_KEY` — without it the collector stays idle.
- `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` — until set, the captcha check
  is skipped (and logged) rather than locking everyone out.
- SMTP — required for the 6-digit verification code.
- `GTL_LEGAL_*` — publisher, address, registration number, publication
  director, host and DPO. These are factual statements about a real company and
  appear as visible `[[…]]` placeholders in the legal pages until filled in.
- Legal texts in pt / es / it / de: fr and en are published; the other four fall
  back to French with a visible notice until translations are published from
  the admin.
