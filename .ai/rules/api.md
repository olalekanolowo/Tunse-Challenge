---
paths:
  - 'app/Services/**,app/Http/Controllers/Api/ClaimController.php'
---

# Api

## Scoring/leaderboard/risk logic lives in app/Services, never in controllers
ChallengeIdGenerator, PhoneNormalizer, ScoringService, ClaimUpgradeService, DuplicateDetectionService, LeaderboardService, RiskFlagService, ActivityLogger, CsvExportService each own one concern. Challenge IDs (`TCH-<SHORTCODE>-<SEQ>`) are only ever generated via ChallengeIdGenerator inside `DB::transaction()` + `lockForUpdate()` on the institution row — never inline elsewhere. Leaderboards are computed on read (not cached); only `leaderboard_snapshots.payload` is a frozen artifact, created explicitly via POST /admin/leaderboard-snapshots and blocked for snapshot_type=final unless the phase is closed.

## Recruit phone is exclusive per phase across students
Supersedes the original "flag, don't block" design: since 2026-11-02 a recruit phone already claimed by another student in the same phase is rejected (422 on recruit_phone) via DuplicateDetectionService::assertPhoneNotClaimedByAnotherStudent on store and on phone-changing updates. Rejected claims don't count, so an admin rejecting a false claim frees the number. Different phases are allowed. findDuplicatesFor()/duplicate_phone risk flag stays for legacy rows and races.
