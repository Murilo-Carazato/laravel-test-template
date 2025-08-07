<?php

namespace Database\Factories;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditFactory extends Factory
{
    protected $model = Audit::class;

    public function definition()
    {
        $actionTypes = ['created', 'updated', 'deleted', 'viewed', 'login', 'logout'];
        $entities = ['user', 'profile', 'feature', 'settings'];
        
        $modelTypes = [
            User::class => 'user',
            \App\Models\Profile::class => 'profile',
            \App\Models\Feature::class => 'feature'
        ];
        
        $modelType = $this->faker->randomElement(array_keys($modelTypes));
        
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement($actionTypes),
            'entity' => $this->faker->randomElement($entities),
            'data' => [
                'details' => $this->faker->sentence(),
                'timestamp' => now()->timestamp
            ],
            'model_type' => $modelType,
            'model_id' => rand(1, 50),
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
        ];
    }
}