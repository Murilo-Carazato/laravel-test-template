<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class GetUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_correct_users(): void
    {
        // Arrange: Create users in the database
        $authenticatedUser = User::factory()->create();
        $users = User::factory()->count(3)->create();
        
        Sanctum::actingAs($authenticatedUser);

        // Act: Retrieve users from the API
        $response = $this->getJson('/api/users');

        // Assert: Check that the correct users are returned
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total'
                    ]
                ]);

        // Check total users (3 created + 1 authenticated user)
        $this->assertEquals(4, $response->json('meta.total'));
    }

    public function test_it_supports_pagination(): void
    {
        // Arrange: Create a large number of users
        $authenticatedUser = User::factory()->create();
        User::factory()->count(25)->create();
        
        Sanctum::actingAs($authenticatedUser);

        // Act: Retrieve users with pagination
        $response = $this->getJson('/api/users?per_page=10&page=2');

        // Assert: Check that the pagination works as expected
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                        'from',
                        'to'
                    ],
                    'links' => [
                        'first',
                        'last',
                        'prev',
                        'next'
                    ]
                ]);

        $this->assertEquals(2, $response->json('meta.current_page'));
        $this->assertEquals(10, $response->json('meta.per_page'));
        $this->assertEquals(26, $response->json('meta.total')); // 25 + 1 authenticated user
        $this->assertCount(10, $response->json('data'));
    }

    public function test_it_supports_filtering(): void
    {
        // Arrange: Create users with different attributes
        $authenticatedUser = User::factory()->create();
        $johnUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        $janeUser = User::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);
        $bobUser = User::factory()->create([
            'name' => 'Bob Johnson',
            'email' => 'bob@example.com'
        ]);
        
        Sanctum::actingAs($authenticatedUser);

        // Act: Retrieve users with specific filters
        $response = $this->getJson('/api/users?name=John');

        // Assert: Check that the filtering works as expected
        $response->assertStatus(200);
        
        $userData = $response->json('data');
        $this->assertCount(1, $userData);
        $this->assertEquals('John Doe', $userData[0]['name']);
        $this->assertEquals('john@example.com', $userData[0]['email']);
    }

    public function test_it_supports_email_filtering(): void
    {
        // Arrange
        $authenticatedUser = User::factory()->create();
        $user1 = User::factory()->create(['email' => 'test1@example.com']);
        $user2 = User::factory()->create(['email' => 'test2@example.com']);
        $user3 = User::factory()->create(['email' => 'different@domain.com']);
        
        Sanctum::actingAs($authenticatedUser);

        // Act
        $response = $this->getJson('/api/users?email=test1@example.com');

        // Assert
        $response->assertStatus(200);
        
        $userData = $response->json('data');
        $this->assertCount(1, $userData);
        $this->assertEquals('test1@example.com', $userData[0]['email']);
    }

    public function test_it_supports_multiple_filters(): void
    {
        // Arrange
        $authenticatedUser = User::factory()->create();
        $targetUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        $otherUser1 = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'johnsmith@example.com'
        ]);
        $otherUser2 = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ]);
        
        Sanctum::actingAs($authenticatedUser);

        // Act
        $response = $this->getJson('/api/users?name=John&email=john@example.com');

        // Assert
        $response->assertStatus(200);
        
        $userData = $response->json('data');
        $this->assertCount(1, $userData);
        $this->assertEquals('John Doe', $userData[0]['name']);
        $this->assertEquals('john@example.com', $userData[0]['email']);
    }

    public function test_unauthenticated_user_cannot_access_users_list(): void
    {
        // Arrange: Create users but don't authenticate
        User::factory()->count(3)->create();

        // Act: Try to retrieve users without authentication
        $response = $this->getJson('/api/users');

        // Assert: Should be unauthorized
        $response->assertStatus(401);
    }

    public function test_empty_results_when_no_users_match_filter(): void
    {
        // Arrange
        $authenticatedUser = User::factory()->create();
        User::factory()->count(3)->create([
            'name' => 'John Doe'
        ]);
        
        Sanctum::actingAs($authenticatedUser);

        // Act: Filter for non-existent name
        $response = $this->getJson('/api/users?name=NonExistentUser');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
        $this->assertEquals(0, $response->json('meta.total'));
    }

    public function test_default_pagination_parameters(): void
    {
        // Arrange
        $authenticatedUser = User::factory()->create();
        User::factory()->count(20)->create();
        
        Sanctum::actingAs($authenticatedUser);

        // Act: Request without pagination parameters
        $response = $this->getJson('/api/users');

        // Assert: Should use default pagination
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertEquals(15, $response->json('meta.per_page')); // Assuming default is 15
        $this->assertEquals(21, $response->json('meta.total')); // 20 + 1 authenticated user
    }
}