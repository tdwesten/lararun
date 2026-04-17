# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This App Does

Lararun is a self-hosted AI running coach. Users authenticate via Strava OAuth, which syncs their running activities. OpenAI GPT-4o (via the Prism PHP library) generates coaching feedback on individual runs and personalized 7-day training plans. Daily training recommendations are emailed to users.

## Commands

```bash
# Full dev stack (Vite + queue + logs + server)
composer run dev

# Run tests + PHPStan
composer run test

# Run tests only
php artisan test

# Run a single test file
php artisan test tests/Feature/SomeTest.php

# PHP code formatting
vendor/bin/pint

# Static analysis
vendor/bin/phpstan

# Frontend
npm run lint        # ESLint
npm run format      # Prettier
npm run types       # TypeScript check
npm run build       # Build assets
```

## Architecture

### Backend

**Laravel 12** with **Inertia.js** (React frontend rendered server-side via SSR).

Key patterns:
- **Observer pattern**: `ActivityObserver` triggers AI enrichment and training plan generation when activities are created/updated.
- **Queue jobs**: All AI calls and email sends are async via database queue. Key jobs: `EnrichActivityWithAiJob`, `GenerateWeeklyTrainingPlanJob`, `ImportStravaActivitiesJob`.
- **Service layer**: `StravaApiService` handles Strava API calls with automatic token refresh.
- **Prism PHP**: Structured LLM wrapper used for all OpenAI calls. Returns typed PHP objects via schema definitions.

**Models**: `User`, `Activity` (with heart rate zone data + AI evaluations), `Objective` (training goals), `DailyRecommendation` (AI-generated daily plans).

**Scheduled command**: `app:generate-daily-training-plans` runs daily to generate 7-day plans for all users with active objectives.

### Frontend

**React 19 + TypeScript + Tailwind CSS 4 + Radix UI** components, built with Vite 7.

Pages live in `resources/js/pages/`. Shared components in `resources/js/components/`. Route helpers via Wayfinder (`resources/js/wayfinder/`).

### Localization

The app supports English and Dutch. Translations live in `lang/en/` and `lang/nl/`. All user-facing strings and AI prompts are translated.

## Key Integrations

| Service | Purpose |
|---------|---------|
| Strava OAuth | Activity sync + authentication |
| OpenAI (via Prism) | AI coaching feedback + training plans |
| Laravel Fortify | Auth features (2FA, password reset) |
| Lettermint | Transactional email |
| Sentry | Error tracking |

Required env vars: `OPENAI_API_KEY`, `STRAVA_CLIENT_ID`, `STRAVA_CLIENT_SECRET`, `STRAVA_REDIRECT_URI`.

## Coding Standards

When working on this project, always use the `php-guidelines-from-spatie` skill for PHP/Laravel code.
