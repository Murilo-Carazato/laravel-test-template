<?php

namespace Database\Factories;

use App\Models\Feature;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeatureFlagFactory extends Factory
{
    protected $model = Feature::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word . '_feature',
            'enabled' => $this->faker->boolean(70),
            'description' => $this->faker->sentence(),
            'options' => [
                'percentage' => $this->faker->numberBetween(0, 100),
                'expires_at' => $this->faker->optional(0.3)->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d H:i:s'),
                'target_groups' => $this->faker->randomElements(['admin', 'premium', 'beta', 'regular'], 2),
            ],
        ];
    }
    
    // Feature desabilitada
    public function disabled()
    {
        return $this->state(function (array $attributes) {
            return ['enabled' => false];
        });
    }
}