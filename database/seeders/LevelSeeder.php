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
                'description' => 'Bachelor Year 1',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'M1',
                'code' => 'm1',
                'description' => 'Master Year 1',
                'order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($levels as $levelData) {
            Level::updateOrCreate(
                ['code' => $levelData['code']],
                $levelData
            );
        }

        $this->command->info('✓ 2 Levels created: L1, M1');
    }
}
