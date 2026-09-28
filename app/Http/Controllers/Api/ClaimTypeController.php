<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClaimType\StoreClaimTypeRequest;
use App\Http\Requests\ClaimType\UpdateClaimTypeRequest;
use App\Http\Resources\ClaimTypeResource;
use App\Models\ClaimType;
use App\Models\Phase;

class ClaimTypeController extends Controller
{
    public function forPhase(Phase $phase)
    {
        return ClaimTypeResource::collection(
            $phase->claimTypes()->where('active', true)->orderBy('code')->get()
        );
    }

    public function store(StoreClaimTypeRequest $request)
    {
        $claimType = ClaimType::create($request->validated() + ['active' => true]);

        return new ClaimTypeResource($claimType);
    }

    public function update(UpdateClaimTypeRequest $request, ClaimType $claimType)
    {
        $claimType->update($request->validated());

        return new ClaimTypeResource($claimType);
    }
}
