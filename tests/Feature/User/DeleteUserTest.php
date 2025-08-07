<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum; // Importar Sanctum
use App\Models\User;

class DeleteUserTest extends TestCase
{
    /** @test */
    public function test_authenticated_user_can_delete_their_own_account(): void
    {
        // Arrange
        $user = User::factory()->create();
        Sanctum::actingAs($user); // Autentica o usuário

        // Act
        $response = $this->deleteJson("/api/v1/users/{$user->id}");

        // Assert
        $response->assertStatus(200) // Assumindo que deleteResponse retorna 200
                 ->assertJson(['message' => 'Resource deleted successfully']);
        
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /** @test */
    public function test_authenticated_user_cannot_delete_another_user_account(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Sanctum::actingAs($user1); // Autentica o usuário 1

        // Act
        $response = $this->deleteJson("/api/v1/users/{$user2->id}");

        // Assert
        $response->assertStatus(403) // Forbidden, pela UserPolicy
                 ->assertJson(['message' => 'Forbidden']);
        
        $this->assertDatabaseHas('users', ['id' => $user2->id]); // Garante que o usuário 2 não foi deletado
    }

    /** @test */
    public function test_unauthenticated_user_cannot_delete_any_account(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->deleteJson("/api/v1/users/{$user->id}");

        // Assert
        $response->assertStatus(401) // Unauthorized
                 ->assertJson(['message' => 'Unauthenticated.']);
        
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /** @test */
    public function test_deleting_non_existent_user_returns_404(): void
    {
        // Arrange
        $user = User::factory()->create(); // Cria um usuário para autenticação
        Sanctum::actingAs($user);

        // Act
        $response = $this->deleteJson('/api/v1/users/999'); // ID que não existe

        // Assert
        $response->assertStatus(404) // Not Found, devido ao Route Model Binding
                 ->assertJson(['message' => 'Resource not found']);
    }
}