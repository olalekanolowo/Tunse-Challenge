<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\Claim;
use App\Models\Institution;
use App\Models\Phase;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    public function __construct(private readonly LeaderboardService $leaderboardService) {}

    public function claims(): StreamedResponse
    {
        $rows = Claim::with(['user.studentProfile', 'institution', 'phase', 'claimType'])
            ->get()
            ->map(fn (Claim $claim) => [
                'claimId' => $claim->id,
                'student' => $claim->user->name,
                'challengeId' => $claim->user->studentProfile?->challenge_id ?? '',
                'institution' => $claim->institution->name,
                'phase' => $claim->phase->name,
                'claimType' => $claim->claimType->label,
                'recruitName' => $claim->recruit_name,
                'recruitPhone' => $claim->recruit_phone,
                'state' => $claim->state,
                'lga' => $claim->lga,
                'status' => $claim->status->value,
                'provisionalPoints' => $claim->provisional_points,
                'auditedPoints' => $claim->audited_points ?? '',
                'createdAt' => $claim->created_at?->toIso8601String(),
            ]);

        return $this->stream('tunse-challenge-claims.csv', $rows);
    }

    public function students(): StreamedResponse
    {
        $rows = StudentProfile::with(['user', 'institution'])
            ->get()
            ->map(fn (StudentProfile $profile) => [
                'challengeId' => $profile->challenge_id ?? '',
                'fullName' => $profile->user->name,
                'email' => $profile->user->email,
                'phone' => $profile->phone,
                'institution' => $profile->institution->name,
                'state' => $profile->state,
                'department' => $profile->department,
                'graduationYear' => $profile->graduation_year,
                'status' => $profile->status->value,
            ]);

        return $this->stream('tunse-challenge-students.csv', $rows);
    }

    public function institutions(): StreamedResponse
    {
        $rows = Institution::all()->map(fn (Institution $institution) => [
            'name' => $institution->name,
            'shortCode' => $institution->short_code,
            'state' => $institution->state,
            'active' => $institution->active ? 'true' : 'false',
            'coordinatorName' => $institution->coordinator_name ?? '',
            'createdAt' => $institution->created_at?->toIso8601String(),
        ]);

        return $this->stream('tunse-challenge-institutions.csv', $rows);
    }

    public function auditLog(): StreamedResponse
    {
        $rows = Audit::with('auditor')->get()->map(fn (Audit $audit) => [
            'auditId' => $audit->id,
            'claimId' => $audit->claim_id,
            'auditor' => $audit->auditor->name,
            'outcome' => $audit->outcome->value,
            'backendLookupKey' => $audit->backend_lookup_key ?? '',
            'auditNotes' => $audit->audit_notes ?? '',
            'auditedAt' => $audit->audited_at?->toIso8601String(),
        ]);

        return $this->stream('tunse-challenge-audit-log.csv', $rows);
    }

    public function institutionLeaderboard(Phase $phase): StreamedResponse
    {
        $rows = $this->leaderboardService->institutionLeaderboard($phase)->map(fn (array $row) => [
            'rank' => $row['rank'],
            'institution' => $row['institution_name'],
            'state' => $row['state'],
            'phase' => $phase->name,
            'provisionalScore' => $row['provisional_score'],
            'auditedScore' => $row['audited_score'],
            'verifiedParticipants' => $row['verified_participant_count'],
            'totalParticipants' => $row['total_participant_count'],
        ]);

        return $this->stream("tunse-challenge-institution-leaderboard-{$phase->id}.csv", $rows);
    }

    public function individualLeaderboard(Phase $phase): StreamedResponse
    {
        $rows = $this->leaderboardService->individualLeaderboard($phase)->map(fn (array $row) => [
            'rank' => $row['rank'],
            'fullName' => $row['full_name'],
            'challengeId' => $row['challenge_id'] ?? '',
            'institution' => $row['institution_name'],
            'phase' => $phase->name,
            'provisionalScore' => $row['provisional_score'],
            'auditedScore' => $row['audited_score'],
            'verifiedClaimCount' => $row['verified_claim_count'],
            'disqualified' => $row['disqualified'] ? 'true' : 'false',
        ]);

        return $this->stream("tunse-challenge-individual-leaderboard-{$phase->id}.csv", $rows);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function stream(string $filename, Collection $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            if ($rows->isNotEmpty()) {
                fputcsv($handle, array_keys($rows->first()));
            }

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
