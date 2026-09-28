---
paths:
  - 'app/Http/Controllers/Api/**'
---

# Controllers Api

## Eloquent create() doesn't reflect DB column defaults on the in-memory model
Every column with a DB-level `->default(...)` (status enums, `active` booleans, etc.) must also be explicitly passed into `Model::create([...])`/`update([...])`, or the model instance returned by that same request will serialize that attribute as null even though the DB row has the default. Bit us on `Claim.status`, `Institution.active`, `ClaimType.active` — always set these explicitly in the controller rather than relying on the migration default.
