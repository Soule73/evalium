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
            ['name' => 'BTS 1ère année', 'code' => 'bts_1'],
            ['name' => 'BTS 2ème année', 'code' => 'bts_2'],
            ['name' => 'Licence 1ère année', 'code' => 'licence_1'],
            ['name' => 'Licence 2ème année', 'code' => 'licence_2'],
            ['name' => 'Licence 3ème année', 'code' => 'licence_3'],
            ['name' => 'Master 1ère année', 'code' => 'master_1'],
            ['name' => 'Master 2ème année', 'code' => 'master_2'],
        ];

        $level = $this->faker->randomElement($levelTypes);

        return [
            'name' => $level['name'],
            'code' => $level['code'] . '_' . $this->faker->unique()->randomNumber(3),
            'description' => $this->faker->optional()->sentence(),
            'order' => $order++,
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
