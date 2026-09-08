# GoodTripLove SEO AI Manager Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a production-ready weekly multilingual SEO page generator to GoodTripLove.

**Architecture:** Store generated page topics separately from translations, generate structured content with OpenAI through Laravel HTTP, quality-gate before publication, expose one public discover route and one protected admin manager, and schedule one run per week.

**Tech Stack:** Laravel 13, PHP 8.3+, MariaDB, Blade, Laravel HTTP client, Laravel scheduler.

**Spec:** `docs/superpowers/specs/2026-09-08-goodtriplove-seo-ai-design.md`

## Global Constraints
- Keep compatibility with the existing no-Node production deployment.
- Add no Composer runtime dependency.
- Use database-backed configuration and encrypted secrets.
- Never publish thin or unsupported pages automatically.
- Generate at most one new page per weekly run.
- Support every locale from `config('goodtriplove.locales')`.

---

### Task 1: Persistence and configuration
Create migrations/models for SEO pages, translations and module settings; add config defaults and encrypted API-key storage.

### Task 2: Topic planner and quality gate
Implement deterministic topic selection based on active countries/cities/categories and published catalogue volume, plus a 0–100 quality score.

### Task 3: OpenAI generation service
Implement Responses API call with strict JSON output parsing, six-language generation and factual grounding from catalogue data.

### Task 4: Weekly command and scheduler integration
Implement `gtl:seo-ai-weekly` and a Monday 04:45 schedule with overlap protection. Enforce one topic per run and 52-week topic cooldown.

### Task 5: Public discover page
Add controller, route and Blade view with SEO metadata, hreflang, JSON-LD, internal links, supporting places and videos.

### Task 6: Admin manager
Add protected admin routes/controller/view for settings, manual generation, publish/unpublish and page inspection.

### Task 7: Installer and verification
Ship an idempotent installer that copies module files and patches `routes/web.php`, `routes/admin.php`, and `routes/console.php`; provide uninstall notes and lint all PHP files.
