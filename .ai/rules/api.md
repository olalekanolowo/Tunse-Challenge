---
paths:
  - 'app/Services/**,app/Http/Controllers/Api/ClaimController.php'
---

# Api

## Scoring/leaderboard/risk logic lives in app/Services, never in controllers
ChallengeIdGenerator, PhoneNormalizer, ScoringService, ClaimUpgradeService, DuplicateDetectionService, LeaderboardService, RiskFlagService, ActivityLogger, CsvExportService each own one concern. Challenge IDs (`TCH-<SHORTCODE>-<SEQ>`) are only ever generated via ChallengeIdGenerator inside `DB::transaction()` + `lockForUpdate()` on the institution row — never inline elsewhere. Leaderboards are computed on read (not cached); only `leaderboard_snapshots.payload` is a frozen artifact, created explicitly via POST /admin/leaderboard-snapshots and blocked for snapshot_type=final unless the phase is closed.
