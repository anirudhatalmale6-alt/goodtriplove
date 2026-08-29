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
Dashboard · videos (moderation, bulk actions, place linking) · **duplicates** ·
places · countries / cities / categories · video collector · ads and scrolling
announcements · **professionals** · users and **member records** · **site
settings** · **SEO** · **action log** · app releases · Security Center ·
Growth & Ops · data quality · reports · legal texts · service status ·
YouTube quota · feature flags · error centre.

- **Site settings** — name, tagline, footer text, contact details and social
  links, editable per language, declared once in `app/Support/SiteSettings.php`
  so the form, its validation and the rendered page cannot drift apart. An empty
  translation falls back to the site's own default language.
- **Member records** — registration date, verification and 2FA status, the
  places the member submitted, their moderation history and security log.
  Deleting is a *soft* delete: a business account owns places, so it has to be
  undoable. Sign-in is refused while an account is deleted.
- **Duplicates** — the unique index on `(provider, provider_video_id)` cannot
  see the same clip reposted under a new id. Titles are compared after
  normalising (hashtags, punctuation and emoji dropped). Resolving a group
  rejects the extra copies; nothing is deleted.
- **Announcements** — six languages, display order, start/end dates, and a
  choice of placement (scrolling bar or footer notice), optionally limited to
  the home page.
- **SEO** — title, description, canonical, indexability and structured data per
  page and per language, overriding what the page itself declares.
- **Keys & security** — 2FA on/off, Turnstile on/off with both keys, the
  YouTube API key and the SMTP settings, declared in
  `app/Support/SystemSettings.php`. Each declaration names the configuration
  entry it overrides; `SystemSettings::apply()` pushes the saved values into
  the live config at boot, so `TurnstileService`, `YouTubeClient` and the
  mailer are unchanged and simply get a different answer from `config()`. A
  setting that was never saved leaves the `.env` value alone. Secrets are
  encrypted at rest and never re-rendered. Each section can be **tested**
  against the real service, because a saved key proves nothing on its own.

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

## Mail on this host

Two settings look wrong and are not:

- `MAIL_MAILER=smtp` to `127.0.0.1:25`, **not** `sendmail`. The web PHP has
  `proc_open` in `disable_functions`, and the sendmail transport forks a
  process, so it throws — after the account row has been written. The command
  line has no such restriction, so a console send reports success while every
  registration on the site returns a 500. Reproduce over HTTP, never in tinker.
- `MAIL_AUTO_TLS=false`. The local MTA offers STARTTLS with a certificate
  issued for its own hostname, so upgrading a connection to `127.0.0.1` fails
  the name check. The hop never leaves the machine; the MTA still uses TLS for
  the real delivery. **Any non-loopback host must leave this at its default.**

The verification notification is queued because that MTA holds its SMTP
greeting for a fixed 15 seconds. The scheduler keeps a worker alive for the
minute so a code is picked up in seconds rather than at the next five-minute
tick.

The sign-up email rule is `email:rfc`, deliberately without `dns`: the MX
lookup cost 15s per submission on this host, and a code the visitor has to read
out of the mailbox proves more than an MX record does.

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
- **Outbound mail** — set from **Admin → Clés & sécurité**, no `.env` edit.
  Local delivery works; nothing reaches an external address yet, and there are
  three separate reasons, so fixing only one changes nothing:
  1. The local MTA answers `550 relay not permitted` to any external recipient
     from an unauthenticated client.
  2. Outbound port 25 is refused to every external MX — Gmail's and OVH's
     alike — while port 443 connects in 5 ms. That is provider-level filtering.
  3. SPF is `v=spf1 include:mx.ovh.com -all`, which expands to two /32s plus
     two `ptr:` mechanisms. This host is in neither, and its PTR is
     `vps-…​.vps.ovh.net`, so the `ptr:` rules do not match either.

  Relaying through `ssl0.ovh.net:587` with a real OVH mailbox settles all
  three at once — authentication makes relaying legitimate, 587 is open, and
  the mail then leaves from servers the published SPF already authorises. **No
  DNS change is needed**, which matters because the DirectAdmin DNS zone for
  this domain is not the published one: the registry delegates
  `goodtriplove.com` to OVH's nameservers, so the zone editable in DirectAdmin
  is complete, correct and entirely unread.
- `GTL_LEGAL_*` — publisher, address, registration number, publication
  director, host and DPO. These are factual statements about a real company and
  appear as visible `[[…]]` placeholders in the legal pages until filled in.
- Legal texts in pt / es / it / de: fr and en are published; the other four fall
  back to French with a visible notice until translations are published from
  the admin.
