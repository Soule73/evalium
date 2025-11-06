<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LevelSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $levels = [
            [
                'name' => 'BTS 1ère année',
                'code' => 'bts_1',
                'description' => 'Première année de Brevet de Technicien Supérieur',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'BTS 2ème année',
                'code' => 'bts_2',
                'description' => 'Deuxième année de Brevet de Technicien Supérieur',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Licence 1ère année',
                'code' => 'licence_1',
                'description' => 'Première année de Licence (L1)',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Licence 2ème année',
                'code' => 'licence_2',
                'description' => 'Deuxième année de Licence (L2)',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Licence 3ème année',
                'code' => 'licence_3',
                'description' => 'Troisième année de Licence (L3)',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Master 1ère année',
                'code' => 'master_1',
                'description' => 'Première année de Master (M1)',
                'order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Master 2ème année',
                'code' => 'master_2',
                'description' => 'Deuxième année de Master (M2)',
                'order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Doctorat 1ère année',
                'code' => 'doctorat_1',
                'description' => 'Première année de Doctorat',
                'order' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'Doctorat 2ème année',
                'code' => 'doctorat_2',
                'description' => 'Deuxième année de Doctorat',
                'order' => 9,
                'is_active' => true,
            ],
            [
                'name' => 'Doctorat 3ème année',
                'code' => 'doctorat_3',
                'description' => 'Troisième année de Doctorat',
                'order' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($levels as $levelData) {
            Level::updateOrCreate(
                ['code' => $levelData['code']],
                $levelData
            );
        }
    }
}
