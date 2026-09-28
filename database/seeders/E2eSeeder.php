<?php

namespace Database\Seeders;

use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\Institution;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Deterministic fixtures for Playwright e2e runs: known-credential accounts
 * on top of the same reference/demo data used for local development, so
 * tests never have to register a staff account through the UI.
 */
class E2eSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StatesLgaSeeder::class,
            CategorySeeder::class,
            ChallengeDemoSeeder::class,
        ]);

        User::updateOrCreate(['email' => 'e2e-admin@tunse.test'], [
            'name' => 'E2E Admin',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
        ]);

        User::updateOrCreate(['email' => 'e2e-auditor@tunse.test'], [
            'name' => 'E2E Auditor',
            'password' => Hash::make('password'),
            'role' => UserRole::Auditor,
        ]);

        $institution = Institution::where('short_code', 'UNILAG')->firstOrFail();

        $student = User::updateOrCreate(['email' => 'e2e-student@tunse.test'], [
            'name' => 'E2E Student',
            'password' => Hash::make('password'),
            'role' => UserRole::Student,
        ]);

        StudentProfile::updateOrCreate(['user_id' => $student->id], [
            'institution_id' => $institution->id,
            'challenge_id' => 'TCH-UNILAG-E2E1',
            'phone' => '+2348010000001',
            'state' => 'Lagos',
            'student_id_number' => 'E2E/0001',
            'department' => 'Computer Science',
            'graduation_year' => 2027,
            'status' => StudentStatus::Approved,
            'approved_at' => now(),
        ]);
    }
}
