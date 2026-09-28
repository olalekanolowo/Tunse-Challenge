<?php

namespace App\Http\Controllers\Api;

use App\Enums\PhaseStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Phase\StorePhaseRequest;
use App\Http\Requests\Phase\TransitionPhaseRequest;
use App\Http\Requests\Phase\UpdatePhaseRequest;
use App\Http\Resources\PhaseResource;
use App\Models\Phase;
use App\Services\ActivityLogger;
use Illuminate\Validation\ValidationException;

class PhaseController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index()
    {
        return PhaseResource::collection(Phase::orderBy('number')->get());
    }

    public function store(StorePhaseRequest $request)
    {
        $phase = Phase::create($request->validated() + ['status' => PhaseStatus::Draft]);

        return new PhaseResource($phase);
    }

    public function update(UpdatePhaseRequest $request, Phase $phase)
    {
        $phase->update($request->validated());

        return new PhaseResource($phase);
    }

    public function transition(TransitionPhaseRequest $request, Phase $phase)
    {
        $target = PhaseStatus::from($request->validated()['status']);

        if ($target === PhaseStatus::Open && $phase->status === PhaseStatus::Frozen) {
            if ($request->user()->role !== UserRole::SuperAdmin) {
                throw ValidationException::withMessages([
                    'status' => ['Only a super admin may reopen a frozen phase.'],
                ]);
            }
        } elseif (! $phase->status->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot transition phase from {$phase->status->value} to {$target->value}."],
            ]);
        }

        $from = $phase->status;
        $phase->update(['status' => $target]);

        $this->activityLogger->record('phase.transitioned', $phase, [
            'from' => $from->value,
            'to' => $target->value,
        ], $request->user());

        return new PhaseResource($phase);
    }
}
