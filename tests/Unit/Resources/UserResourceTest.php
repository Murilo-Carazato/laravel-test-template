<?php

namespace Tests\Unit\Resources;

use Tests\TestCase;
use App\Http\Resources\UserResource;
use App\Http\Resources\ProfileResource; // Importar ProfileResource
use App\Models\User;
use App\Models\Profile; // Importar Profile
use Illuminate\Http\Request; // Importar Request
use Illuminate\Database\Eloquent\Collection; // Importar Collection

class UserResourceTest extends TestCase
{
    /** @test */
    public function test_user_resource_transforms_correctly_without_profile(): void
    {
        // Arrange
        $user = User::factory()->make([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => now(),
            'updated_at' => now(),
            'email_verified_at' => now()
        ]);

        // Act
        $resource = new UserResource($user);
        $response = $resource->toArray(new Request());

        // Assert
        $this->assertEquals([
            'id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'email_verified_at' => $user->email_verified_at->toISOString(),
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
            'profile' => null // Deve ser null se não carregado
        ], $response);
    }

    /** @test */
    public function test_user_resource_transforms_correctly_with_profile_loaded(): void
    {
        // Arrange
        $user = User::factory()->make([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'created_at' => now(),
            'updated_at' => now(),
            'email_verified_at' => now()
        ]);
        $profile = Profile::factory()->make([
            'user_id' => $user->id,
            'bio' => 'A test bio',
            'phone' => '1234567890',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subHour()
        ]);
        $user->setRelation('profile', $profile); // Carregar a relação manualmente

        // Act
        $resource = new UserResource($user);
        $response = $resource->toArray(new Request());

        // Assert
        $this->assertIsArray($response['profile']);
        $this->assertEquals($profile->id, $response['profile']['id']);
        $this->assertEquals($profile->bio, $response['profile']['bio']);
        $this->assertEquals($profile->phone, $response['profile']['phone']);
    }

    /** @test */
    public function test_user_resource_collection_transforms_correctly(): void
    {
        // Arrange
        $users = User::factory()->count(3)->make(); // Use make para não persistir no DB

        // Act
        $collection = UserResource::collection($users);
        $response = $collection->toArray(new Request());

        // Assert
        $this->assertCount(3, $response);
        foreach ($response as $index => $userArray) {
            $this->assertArrayHasKey('id', $userArray);
            $this->assertArrayHasKey('name', $userArray);
            $this->assertArrayHasKey('email', $userArray);
            $this->assertEquals($users[$index]->id, $userArray['id']);
            $this->assertArrayHasKey('profile', $userArray); // Sempre deve ter a chave profile
            $this->assertNull($userArray['profile']); // E deve ser null se não carregado
        }
    }
}