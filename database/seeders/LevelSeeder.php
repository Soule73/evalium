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
                'name' => 'L1',
                'code' => 'l1',
                'description' => 'Licence 1ere anne',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'L2',
                'code' => 'l2',
                'description' => 'Licence 2eme annee',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'M1',
                'code' => 'm1',
                'description' => 'Master 1ere annee',
                'order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($levels as $levelData) {
            Level::updateOrCreate(
                ['code' => $levelData['code']],
                $levelData
            );
        }

        $this->command->info('3 Levels created: L1, L2, M1');
    }
}
