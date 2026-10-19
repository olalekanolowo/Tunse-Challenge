<?php

use App\Models\Institution;
use Database\Seeders\NigerianUniversitiesSeeder;

it('seeds Olabisi Onabanjo University as an active Ogun institution', function () {
    $this->seed(NigerianUniversitiesSeeder::class);

    $institution = Institution::where('short_code', 'OOU')->firstOrFail();

    expect($institution->state)->toBe('Ogun')
        ->and($institution->active)->toBeTrue();
});

it('can be re-run without duplicating institutions', function () {
    $this->seed(NigerianUniversitiesSeeder::class);
    $count = Institution::count();

    $this->seed(NigerianUniversitiesSeeder::class);

    expect(Institution::count())->toBe($count);
});

it('leaves institutions an admin already edited untouched', function () {
    Institution::factory()->create([
        'short_code' => 'OOU',
        'name' => 'OOU (renamed by admin)',
        'active' => false,
    ]);
    Institution::factory()->create([
        'short_code' => 'LASU-OJO',
        'name' => 'Lagos State University',
    ]);

    $this->seed(NigerianUniversitiesSeeder::class);

    expect(Institution::where('short_code', 'OOU')->first())
        ->name->toBe('OOU (renamed by admin)')
        ->active->toBeFalse()
        ->and(Institution::where('name', 'Lagos State University')->count())->toBe(1);
});
