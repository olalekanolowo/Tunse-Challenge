<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommunitySubmission\StoreCommunitySubmissionRequest;
use App\Http\Resources\CommunitySubmissionResource;
use App\Models\CommunitySubmission;
use Illuminate\Http\Request;

class CommunitySubmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = CommunitySubmission::query()->with('user');

        if (! $request->user()->isStaff()) {
            $query->where('user_id', $request->user()->id);
        }

        return CommunitySubmissionResource::collection($query->latest()->paginate(20));
    }

    public function store(StoreCommunitySubmissionRequest $request)
    {
        $user = $request->user();

        $submission = CommunitySubmission::create($request->validated() + [
            'user_id' => $user->id,
            'institution_id' => $user->studentProfile->institution_id,
        ]);

        return (new CommunitySubmissionResource($submission))->response()->setStatusCode(201);
    }

    public function updateScoreNote(Request $request, CommunitySubmission $communitySubmission)
    {
        $request->validate(['score_note' => ['nullable', 'string', 'max:1000']]);

        $communitySubmission->update(['score_note' => $request->input('score_note')]);

        return new CommunitySubmissionResource($communitySubmission);
    }
}
