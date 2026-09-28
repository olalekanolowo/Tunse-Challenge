<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\State;
use Illuminate\Http\JsonResponse;

class ReferenceController extends Controller
{
    public function states(): JsonResponse
    {
        $states = State::with('lgas:id,state_id,name')->orderBy('name')->get()->map(fn (State $state) => [
            'state' => $state->name,
            'lgas' => $state->lgas->pluck('name'),
        ]);

        return response()->json(['data' => $states]);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::where('active', true)->orderBy('label')->get(['id', 'code', 'label', 'bg_color']);

        return response()->json(['data' => $categories]);
    }
}
