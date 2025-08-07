<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class UserWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_user_lifecycle(): void
    {
        // Create user
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $createResponse = $this->postJson('/api/users', $userData);
        $createResponse->assertStatus(201);

        $userId = $createResponse->json('data.id');

        // Authenticate as created user
        $user = User::find($userId);
        Sanctum::actingAs($user);

        // Read user
        $readResponse = $this->getJson("/api/users/{$userId}");
        $readResponse->assertStatus(200)
                   ->assertJson([
                       'data' => [
                           'name' => 'John Doe',
                           'email' => 'john@example.com'
                       ]
                   ]);

        // Update user
        $updateData = [
            'name' => 'John Updated',
            'email' => 'john.updated@example.com'
        ];

        $updateResponse = $this->putJson("/api/users/{$userId}", $updateData);
        $updateResponse->assertStatus(200)
                      ->assertJson([
                          'data' => [
                              'name' => 'John Updated',
                              'email' => 'john.updated@example.com'
                          ]
                      ]);

        // Verify update in database
        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'name' => 'John Updated',
            'email' => 'john.updated@example.com'
        ]);

        // Delete user
        $deleteResponse = $this->deleteJson("/api/users/{$userId}");
        $deleteResponse->assertStatus(204);

        // Verify deletion
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }
}