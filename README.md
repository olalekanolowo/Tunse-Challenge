# Tunse Challenge API

A Laravel API powering the **Tunse Challenge**, a phased submission and scoring platform for students affiliated with institutions. Students submit claims for review, staff audit and score them, and the platform tracks leaderboards, badges, and disqualifications across the life of the challenge.

## Overview

The challenge runs through a series of **phases** (`draft` → `open` → `frozen` → `closed`), each exposing its own set of **claim types** that students can submit against. Submitted claims move through a review workflow, staff can flag or request corrections, and verified claims feed into individual and institutional **leaderboards**.

### Core concepts

- **Users & roles** — `student`, `admin`, `auditor`, and `super_admin` (`app/Enums/UserRole.php`), with staff roles grouped for authorization checks.
- **Phases** — challenge stages with controlled transitions (`app/Enums/PhaseStatus.php`, `app/Services/*`), each scoped to a set of claim types.
- **Claims** — student submissions with a review lifecycle: `submitted`, `verified`, `rejected`, `flagged`, `correction_requested` (`app/Enums/ClaimStatus.php`).
- **Student profiles** — approval state per student (`pending`, `approved`, `disqualified`) with ID verification.
- **Institutions** — organizations students belong to, used for institutional leaderboards and admin management.
- **Community submissions** — supplementary submissions reviewed and scored separately from claims.
- **Scoring & leaderboards** — automated scoring, manual score adjustments, duplicate/risk detection, and cumulative/individual/institution leaderboards, with point-in-time snapshots.
- **Badges** — awarded to students based on participation and achievements.
- **Disqualifications** — staff-issued disqualifications with reinstatement support.
- **Audit trail & exports** — an activity log of review actions, plus CSV exports for claims, students, institutions, audit logs, and leaderboards.

### Key API areas (`routes/api.php`)

- `auth/*` — registration, login, logout, current user.
- `claims/*` — student claim submission, listing, updates, and photo retrieval.
- `community-submissions/*` — community submission listing and creation.
- `phases`, `phases/{phase}/claim-types`, `categories`, `states`, `institutions`, `badges` — reference and lookup data.
- `leaderboards/{cumulative,individual,institution}` — public leaderboard views.
- `admin/*` — staff-only endpoints for managing phases, claim types, institutions, claims review, disqualifications, score adjustments, leaderboard snapshots, the admin dashboard, and CSV exports.

Authentication is handled via [Laravel Sanctum](https://laravel.com/docs/sanctum).

## Tech stack

- PHP 8.5 / Laravel
- SQLite (default local database)
- Pest for testing

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Run the local dev environment:

```bash
composer run dev
```

Run the test suite:

```bash
php artisan test --compact
```

## Project rules

Domain-specific conventions and constraints for this codebase are documented under [`.ai/rules`](.ai/rules), indexed by file path in [`.ai/rules/index.md`](.ai/rules/index.md).
