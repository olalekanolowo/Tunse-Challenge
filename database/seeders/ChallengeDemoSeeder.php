<?php

namespace Database\Seeders;

use App\Models\ClaimType;
use App\Models\Institution;
use App\Models\Phase;
use Illuminate\Database\Seeder;

class ChallengeDemoSeeder extends Seeder
{
    /**
     * Mirrors src/data/challenge/{institutions,phases,claim-types}.ts in the frontend repo,
     * so dev/demo data lines up with the fixtures the UI was originally built against.
     */
    public function run(): void
    {
        $institutions = [
            ['University of Lagos', 'UNILAG', 'Lagos', true, 'Dr. Adeola Fashola'],
            ['University of Ibadan', 'UI', 'Oyo', true, 'Mr. Tunde Bakare'],
            ['Obafemi Awolowo University', 'OAU', 'Osun', true, 'Mrs. Funmilayo Ige'],
            ['Ahmadu Bello University', 'ABU', 'Kaduna', true, null],
            ['University of Nigeria, Nsukka', 'UNN', 'Enugu', true, 'Mr. Chidi Okeke'],
            ['Covenant University', 'COVENANT', 'Ogun', true, null],
            ['Federal University of Technology, Akure', 'FUTA', 'Ondo', true, 'Miss Blessing Ayo'],
            ['University of Benin', 'UNIBEN', 'Edo', false, null],
        ];

        foreach ($institutions as [$name, $shortCode, $state, $active, $coordinator]) {
            Institution::updateOrCreate(['short_code' => $shortCode], [
                'name' => $name,
                'state' => $state,
                'active' => $active,
                'coordinator_name' => $coordinator,
            ]);
        }

        $phases = [
            [0, 'Community & Social Media Engagement', '2026-08-01', '2027-02-15', 'open', 'N500,000 Creative Impact Prize to top institution/team.'],
            [1, 'Build the Tunse Workforce', '2026-08-01', '2026-10-15', 'open', 'Top institution: N500,000. Individual leaders: N30,000-N50,000.'],
            [2, 'Grow the Tunse Market', '2026-10-16', '2026-12-15', 'draft', 'Top institution: N500,000. Individual leaders: N30,000-N50,000.'],
            [3, 'Put Tunse to Work', '2026-12-16', '2027-02-15', 'draft', 'Prize to be finalized based on Phase 1-2 learning.'],
        ];

        $phaseModels = [];
        foreach ($phases as [$number, $name, $startsAt, $endsAt, $status, $prizeText]) {
            $phaseModels[$number] = Phase::updateOrCreate(['number' => $number], [
                'name' => $name,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $status,
                'prize_text' => $prizeText,
                'rules_version' => 'v1.0',
            ]);
        }

        $claimTypes = [
            [1, 'verified_tworker', 'Verified T-worker', 10, 'Recruit completes Tunse registration and becomes a Verified T-worker.'],
            [1, 'active_verifier', 'Active Verifier', 25, 'Recruit qualifies as and becomes an active Tunse Verifier. If a person qualifies as both T-worker and Verifier, only the higher Verifier value counts.'],
            [2, 'registered_customer', 'Registered Customer', 5, 'Recruit registers as a customer on Tunse.'],
            [2, 'qualified_vendor', 'Qualified Vendor', null, 'Recruit completes the required Tunse vendor profile/registration. Points pending finalization during pilot.'],
            [3, 'completed_job', 'Completed Job', 15, 'A service request through Tunse is completed.'],
            [3, 'repeat_patronage', 'Repeat Patronage', 20, 'Same customer returns for a repeat completed job.'],
            [3, 'rated_job', 'Rated Job', 5, 'Customer leaves a rating for a completed job.'],
            [3, 'vendor_linked_transaction', 'Vendor-linked Transaction', 10, 'A T-worker transaction is linked to a recruited vendor.'],
        ];

        foreach ($claimTypes as [$phaseNumber, $code, $label, $basePoints, $ruleText]) {
            ClaimType::updateOrCreate(
                ['phase_id' => $phaseModels[$phaseNumber]->id, 'code' => $code],
                ['label' => $label, 'base_points' => $basePoints, 'validation_rule_text' => $ruleText]
            );
        }
    }
}
