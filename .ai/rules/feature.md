---
paths:
  - 'tests/Feature/**'
---

# Feature

## Sanctum's RequestGuard caches the resolved user across requests in one Pest test
Within a single test, the container (and its Auth guard instances) persists across multiple simulated `$this->postJson()`/`getJson()` calls, so a guard that already resolved a user (or resolved "no user") returns that cached result on a later call even after e.g. logout or switching actors. Always build request headers via the shared `apiAuthHeader($user)` helper in tests/Pest.php — it calls `Auth::forgetGuards()` before minting a token — rather than hand-building an `Authorization` header, whenever a test authenticates as more than one user or re-checks auth state after a mutation (logout, token revocation).
