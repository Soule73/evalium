<?php

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

class LevelFactory extends Factory
{
    protected $model = Level::class;

    public function definition(): array
    {
        static $order = 1;

        $levelTypes = [
            ['name' => 'BTS Year 1', 'code' => 'bts_1'],
            ['name' => 'BTS Year 2', 'code' => 'bts_2'],
            ['name' => 'Bachelor Year 1', 'code' => 'licence_1'],
            ['name' => 'Bachelor Year 2', 'code' => 'licence_2'],
            ['name' => 'Bachelor Year 3', 'code' => 'licence_3'],
            ['name' => 'Master Year 1', 'code' => 'master_1'],
            ['name' => 'Master Year 2', 'code' => 'master_2'],
        ];

        $level = $this->faker->randomElement($levelTypes);
        $uniqueSuffix = $this->faker->unique()->randomNumber(3);

        return [
            'name' => $level['name'].' - '.$uniqueSuffix,
            'code' => $level['code'].'_'.$uniqueSuffix,
            'description' => $this->faker->optional()->sentence(),
            'order' => $order++,
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
