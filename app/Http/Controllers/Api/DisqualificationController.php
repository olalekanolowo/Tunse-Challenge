<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDisqualificationRequest;
use App\Http\Resources\DisqualificationResource;
use App\Models\Disqualification;
use App\Models\Institution;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class DisqualificationController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request)
    {
        $query = Disqualification::query();

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->filled('targetable_type')) {
            $type = $request->string('targetable_type')->value() === 'institution'
                ? Institution::class
                : User::class;
            $query->where('targetable_type', $type);
        }

        return DisqualificationResource::collection($query->latest()->get());
    }

    public function store(StoreDisqualificationRequest $request)
    {
        $disqualification = Disqualification::create([
            'targetable_type' => $request->targetableModelClass(),
            'targetable_id' => $request->input('targetable_id'),
            'phase_id' => $request->input('phase_id'),
            'reason' => $request->input('reason'),
            'admin_id' => $request->user()->id,
            'active' => true,
        ]);

        $this->activityLogger->record('disqualification.created', $disqualification, [
            'reason' => $disqualification->reason,
        ], $request->user());

        return (new DisqualificationResource($disqualification))->response()->setStatusCode(201);
    }

    public function reinstate(Request $request, Disqualification $disqualification)
    {
        $disqualification->update(['active' => false]);

        $this->activityLogger->record('disqualification.reinstated', $disqualification, null, $request->user());

        return new DisqualificationResource($disqualification);
    }
}
