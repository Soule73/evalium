<?php

namespace Database\Seeders;

use App\Models\Level;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Seed 12 subjects (4 per level: L1, L2, M1).
     */
    public function run(): void
    {
        $levels = Level::orderBy('order')->get()->keyBy('name');

        $requiredLevels = ['L1', 'L2', 'M1'];

        if (! $levels->has($requiredLevels)) {
            $missing = array_diff($requiredLevels, $levels->keys()->all());
            $this->command->error('Missing required levels: '.implode(', ', $missing).'.');

            return;
        }

        $subjects = [
            ['name' => 'Analyse Mathematique', 'code' => 'MATH_L1', 'level_id' => $levels['L1']->id],
            ['name' => 'Physique Generale', 'code' => 'PHYS_L1', 'level_id' => $levels['L1']->id],
            ['name' => 'Algorithmique', 'code' => 'ALGO_L1', 'level_id' => $levels['L1']->id],
            ['name' => 'Anglais Scientifique', 'code' => 'ANG_L1', 'level_id' => $levels['L1']->id],

            ['name' => 'Algebre Lineaire', 'code' => 'ALG_L2', 'level_id' => $levels['L2']->id],
            ['name' => 'Mecanique des Fluides', 'code' => 'MDF_L2', 'level_id' => $levels['L2']->id],
            ['name' => 'Programmation Orientee Objet', 'code' => 'POO_L2', 'level_id' => $levels['L2']->id],
            ['name' => 'Communication Professionnelle', 'code' => 'COM_L2', 'level_id' => $levels['L2']->id],

            ['name' => 'Optimisation', 'code' => 'OPT_M1', 'level_id' => $levels['M1']->id],
            ['name' => 'Intelligence Artificielle', 'code' => 'IA_M1', 'level_id' => $levels['M1']->id],
            ['name' => 'Bases de Donnees Avancees', 'code' => 'BDA_M1', 'level_id' => $levels['M1']->id],
            ['name' => 'Gestion de Projet', 'code' => 'GP_M1', 'level_id' => $levels['M1']->id],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }

        $this->command->info(count($subjects).' Subjects created (4 per level)');
    }
}
