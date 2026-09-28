<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

class StatesLgaSeeder extends Seeder
{
    /**
     * Mirrors src/data/challenge/statesLga.ts in the frontend repo.
     */
    public function run(): void
    {
        $data = [
            'Lagos' => ['Ikeja', 'Eti-Osa', 'Surulere', 'Alimosho', 'Agege'],
            'Oyo' => ['Ibadan North', 'Ibadan South-West', 'Egbeda', 'Akinyele'],
            'Osun' => ['Ile-Ife East', 'Osogbo', 'Ilesa East'],
            'Kaduna' => ['Kaduna North', 'Kaduna South', 'Zaria', 'Sabon Gari'],
            'Enugu' => ['Enugu East', 'Enugu North', 'Nsukka', 'Udi'],
            'Ogun' => ['Abeokuta South', 'Ota', 'Sagamu', 'Ijebu Ode'],
            'Ondo' => ['Akure South', 'Akure North', 'Owo'],
            'Edo' => ['Oredo', 'Egor', 'Ikpoba-Okha', 'Benin City'],
            'Rivers' => ['Port Harcourt', 'Obio-Akpor', 'Eleme'],
            'FCT' => ['Abuja Municipal', 'Gwagwalada', 'Kuje'],
        ];

        foreach ($data as $stateName => $lgas) {
            $state = State::firstOrCreate(['name' => $stateName]);
            foreach ($lgas as $lgaName) {
                $state->lgas()->firstOrCreate(['name' => $lgaName]);
            }
        }
    }
}
