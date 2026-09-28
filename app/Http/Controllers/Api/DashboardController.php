<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Institution;
use App\Models\Phase;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalClaims = Claim::count();
        $verifiedClaims = Claim::where('status', 'verified')->count();
        $rejectedClaims = Claim::where('status', 'rejected')->count();

        return response()->json(['data' => [
            'participants' => User::where('role', UserRole::Student)->count(),
            'institutions' => [
                'active' => Institution::where('active', true)->count(),
                'inactive' => Institution::where('active', false)->count(),
            ],
            'claims_by_type' => Claim::selectRaw('claim_type_id, count(*) as total')
                ->with('claimType:id,label')
                ->groupBy('claim_type_id')
                ->get()
                ->map(fn (Claim $row) => ['claim_type' => $row->claimType?->label, 'total' => $row->total]),
            'claims_by_status' => Claim::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'rejection_rate' => $totalClaims > 0 ? round($rejectedClaims / $totalClaims * 100, 2) : 0,
            'verification_rate' => $totalClaims > 0 ? round($verifiedClaims / $totalClaims * 100, 2) : 0,
            'state_coverage' => Claim::selectRaw('state, count(*) as total')->groupBy('state')->pluck('total', 'state'),
            'lga_coverage' => Claim::selectRaw('lga, count(*) as total')->groupBy('lga')->pluck('total', 'lga'),
            'category_coverage' => Claim::selectRaw('category, count(*) as total')->whereNotNull('category')->groupBy('category')->pluck('total', 'category'),
            'phases_by_status' => Phase::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'pending_audit_count' => Claim::where('status', 'submitted')->count(),
        ]]);
    }
}
