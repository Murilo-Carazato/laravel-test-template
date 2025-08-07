<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum; // Importar Sanctum

class UpdateUserTest extends TestCase
{
    /** @test */
    public function test_authenticated_user_can_update_their_own_account_successfully(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com'
        ]);
        Sanctum::actingAs($user); // Autentica o usuário para que ele possa atualizar a própria conta

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ];

        // Act
        $response = $this->putJson('/api/v1/users/' . $user->id, $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.email', 'updated@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);
    }

    /** @test */
    public function test_authenticated_user_cannot_update_another_user_account(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Sanctum::actingAs($user1); // Autentica o usuário 1

        $updateData = [
            'name' => 'User 2 New Name'
        ];

        // Act
        $response = $this->putJson("/api/v1/users/{$user2->id}", $updateData);

        // Assert
        $response->assertStatus(403); // Forbidden, pela UserPolicy
        $this->assertDatabaseMissing('users', ['name' => 'User 2 New Name']); // Não deve atualizar
    }

    /** @test */
    public function test_cannot_update_user_with_invalid_data(): void
    {
        // Arrange
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $invalidData = [
            'name' => '', // Inválido
            'email' => 'invalid-email' // Inválido
        ];

        // Act
        $response = $this->putJson('/api/v1/users/' . $user->id, $invalidData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    /** @test */
    public function test_cannot_update_user_with_duplicate_email(): void
    {
        // Arrange
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        $userToUpdate = User::factory()->create();
        Sanctum::actingAs($userToUpdate);

        $updateData = [
            'name' => 'New Name',
            'email' => 'existing@example.com' // Email duplicado de outro usuário
        ];

        // Act
        $response = $this->putJson('/api/v1/users/' . $userToUpdate->id, $updateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function test_cannot_update_non_existent_user(): void
    {
        // Arrange
        $user = User::factory()->create(); // Cria um usuário para autenticação
        Sanctum::actingAs($user);

        $updateData = [
            'name' => 'New Name',
            'email' => 'new@example.com'
        ];

        // Act
        $response = $this->putJson('/api/v1/users/999', $updateData); // ID que não existe

        // Assert
        $response->assertStatus(404); // Not Found, devido ao Route Model Binding
    }

    /** @test */
    public function test_can_update_user_with_partial_data(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com'
        ]);
        Sanctum::actingAs($user);

        $updateData = ['name' => 'Updated Name Only']; // Apenas atualiza o nome

        // Act
        $response = $this->putJson('/api/v1/users/' . $user->id, $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name Only')
            ->assertJsonPath('data.email', 'original@example.com'); // Email deve permanecer o mesmo
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name Only',
            'email' => 'original@example.com'
        ]);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_update_any_account(): void
    {
        // Arrange
        $user = User::factory()->create();
        $updateData = ['name' => 'Updated Name'];

        // Act
        $response = $this->putJson("/api/v1/users/{$user->id}", $updateData);

        // Assert
        $response->assertStatus(401); // Unauthorized
        $this->assertDatabaseHas('users', ['name' => $user->name]); // Nome não deve ter sido atualizado
    }
}