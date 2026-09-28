<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBadgeRequest;
use App\Http\Requests\StoreUserBadgeRequest;
use App\Http\Requests\UpdateBadgeRequest;
use App\Http\Resources\BadgeResource;
use App\Http\Resources\UserBadgeResource;
use App\Models\Badge;
use App\Models\UserBadge;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function index()
    {
        return BadgeResource::collection(Badge::where('active', true)->orderBy('name')->get());
    }

    public function myBadges(Request $request)
    {
        return UserBadgeResource::collection(
            UserBadge::where('user_id', $request->user()->id)->with('badge')->get()
        );
    }

    public function store(StoreBadgeRequest $request)
    {
        $badge = Badge::create($request->validated() + ['active' => true]);

        return (new BadgeResource($badge))->response()->setStatusCode(201);
    }

    public function update(UpdateBadgeRequest $request, Badge $badge)
    {
        $badge->update($request->validated());

        return new BadgeResource($badge);
    }

    public function award(StoreUserBadgeRequest $request)
    {
        $data = $request->validated();

        $userBadge = UserBadge::firstOrCreate([
            'user_id' => $data['user_id'],
            'badge_id' => $data['badge_id'],
            'phase_id' => $data['phase_id'],
        ], [
            'awarded_at' => now(),
        ]);

        return (new UserBadgeResource($userBadge->load('badge')))->response()->setStatusCode(201);
    }
}
