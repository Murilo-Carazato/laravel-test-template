<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip_code' => $this->faker->postcode(),
            'avatar' => 'avatars/default-' . rand(1, 5) . '.png', // Imagens default
            'bio' => $this->faker->paragraphs(2, true),
            'preferences' => [
                'theme' => $this->faker->randomElement(['light', 'dark', 'system']),
                'notifications' => $this->faker->boolean(80),
                'language' => $this->faker->randomElement(['pt-BR', 'en', 'es']),
            ],
        ];
    }
}