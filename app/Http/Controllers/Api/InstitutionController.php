<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\StoreInstitutionRequest;
use App\Http\Requests\Institution\UpdateInstitutionRequest;
use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    public function index(Request $request)
    {
        $query = Institution::query();

        if (! $request->user()?->isStaff()) {
            $query->where('active', true);
        } else {
            $query->withCount('studentProfiles');
        }

        return InstitutionResource::collection($query->orderBy('name')->get());
    }

    public function store(StoreInstitutionRequest $request)
    {
        $institution = Institution::create($request->validated() + ['active' => true]);

        return new InstitutionResource($institution);
    }

    public function update(UpdateInstitutionRequest $request, Institution $institution)
    {
        $institution->update($request->validated());

        return new InstitutionResource($institution);
    }
}
