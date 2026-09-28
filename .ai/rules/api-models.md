---
paths:
  - 'app/Http/Controllers/Api/DisqualificationController.php,app/Models/Disqualification.php'
---

# Api Models

## Disqualification "reinstate" flips active=false on the same row
Reinstating a disqualification updates the existing row's `active` to false — it never inserts a new row. This mirrors the frontend's original `adminStore.reinstateDisqualification` semantics exactly and is asserted in tests/Feature/Admin/DisqualificationTest.php.
