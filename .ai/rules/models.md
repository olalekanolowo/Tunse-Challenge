---
paths:
  - 'app/Http/Controllers/Api/**,app/Models/User.php,app/Models/StudentProfile.php'
---

# Models

## Auth is Bearer-token Sanctum only, one users table with role enum
Students and staff (admin/auditor/super_admin) share one `users` table with a `role` (App\Enums\UserRole) column. Student-only fields live on a separate `student_profiles` 1:1 table, not on users. Auth is Sanctum personal-access-token Bearer auth (`createToken()`), never cookie/session auth — there is no `EnsureFrontendRequestsAreStateful`, no CORS credentials. `bootstrap/app.php` sets `redirectGuestsTo(fn () => null)` because this is a pure API with no `login` named route; without it, `auth:sanctum` on a request with a non-JSON Accept header 500s instead of 401ing (Authenticate::redirectTo tries to build a nonexistent 'login' route).
