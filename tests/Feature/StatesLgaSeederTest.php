<?php

use App\Models\Lga;
use App\Models\State;
use Database\Seeders\StatesLgaSeeder;

it('seeds all 36 states plus FCT and their 774 LGAs', function () {
    $this->seed(StatesLgaSeeder::class);

    expect(State::count())->toBe(37)
        ->and(Lga::count())->toBe(774)
        ->and(State::where('name', 'Lagos')->first()->lgas()->pluck('name'))->toContain('Ikorodu')->toHaveCount(20);
});

it('removes non-official LGA names left by earlier seeds and can be re-run', function () {
    State::create(['name' => 'Edo'])->lgas()->create(['name' => 'Benin City']);

    $this->seed(StatesLgaSeeder::class);
    $this->seed(StatesLgaSeeder::class);

    expect(Lga::where('name', 'Benin City')->exists())->toBeFalse()
        ->and(Lga::count())->toBe(774);
});
