<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScoreAdjustmentRequest;
use App\Http\Resources\ScoreAdjustmentResource;
use App\Models\Institution;
use App\Models\ScoreAdjustment;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ScoreAdjustmentController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request)
    {
        $query = ScoreAdjustment::query();

        if ($request->filled('phase_id')) {
            $query->where('phase_id', $request->integer('phase_id'));
        }

        if ($request->filled('target_type') && $request->filled('target_id')) {
            $type = $request->string('target_type')->value() === 'institution' ? Institution::class : User::class;
            $query->where('targetable_type', $type)->where('targetable_id', $request->integer('target_id'));
        }

        return ScoreAdjustmentResource::collection($query->latest()->get());
    }

    public function store(StoreScoreAdjustmentRequest $request)
    {
        $adjustment = ScoreAdjustment::create([
            'targetable_type' => $request->targetableModelClass(),
            'targetable_id' => $request->input('targetable_id'),
            'phase_id' => $request->input('phase_id'),
            'points' => $request->input('points'),
            'reason' => $request->input('reason'),
            'admin_id' => $request->user()->id,
        ]);

        $this->activityLogger->record('score_adjustment.created', $adjustment, [
            'points' => $adjustment->points,
            'reason' => $adjustment->reason,
        ], $request->user());

        return (new ScoreAdjustmentResource($adjustment))->response()->setStatusCode(201);
    }
}
