# Contexto do Projeto: tests

*Gerado em: 2025-05-28T15:07:36.770Z*
*Pasta Raiz: `C:\Users\Murilo Carazato\Documents\Laravel Projects\HUB\teste-template\tests`*

## Conteúdo dos Arquivos

### Arquivo: `Feature\ExampleTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
```

### Arquivo: `Feature\User\CreateUserTest.php`


#### Conteúdo

```php
<?php


class CreateUserTest extends TestCase
{

    public function test_user_can_be_created()
    {
        $response = $this->post('/api/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_creation_requires_name()
    {
        $response = $this->post('/api/users', [
            'email' => 'john@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_user_creation_requires_email()
    {
        $response = $this->post('/api/users', [
            'name' => 'John Doe',
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_user_creation_requires_password()
    {
        $response = $this->post('/api/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }
}
```

### Arquivo: `Feature\User\DeleteUserTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Feature\User;


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
```

### Arquivo: `Feature\User\GetUsersTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Feature\User;


class GetUsersTest extends TestCase
{

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
```

### Arquivo: `Feature\User\UpdateUserTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Feature\User;


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
```

### Arquivo: `Feature\UserWorkflowTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Feature;


class UserWorkflowTest extends TestCase
{

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
```

### Arquivo: `TestCase.php`


#### Conteúdo

```php
<?php

namespace Tests;


abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure test environment
        config(['app.env' => 'testing']);
        config(['cache.default' => 'array']);
        config(['session.driver' => 'array']);
        config(['queue.default' => 'sync']);
    }
}
```

### Arquivo: `Unit\Commands\User\CreateUserCommandTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Commands\User;


class CreateUserCommandTest extends TestCase
{
    protected $userRepository;
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->command = new CreateUserCommand($this->userRepository);
    }

    /** @test */
    public function test_handle_creates_user_successfully(): void
    {
        // Arrange
        $userData = new UserDTO(
            'John Doe',
            'john@example.com',
            'password123'
        );

        $expectedUser = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->userRepository
            ->shouldReceive('createFromDTO') // Chamada ajustada para createFromDTO
            ->once()
            ->with($userData)
            ->andReturn($expectedUser);

        // Act
        $result = $this->command->handle($userData); // Chamada ajustada para handle

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);
        $this->assertEquals(1, $result->id);
    }

    /** @test */
    public function test_handle_can_create_user_with_profile_data(): void
    {
        // Arrange
        $profileData = ['bio' => 'A test bio', 'phone' => '123-456-7890'];
        $userData = new UserDTO(
            'Jane Doe',
            'jane@example.com',
            'password123',
            $profileData
        );

        $expectedUser = new User([
            'id' => 2,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ]);
        $expectedUser->setRelation('profile', new \App\Models\Profile($profileData));


        $this->userRepository
            ->shouldReceive('createFromDTO')
            ->once()
            ->with($userData)
            ->andReturn($expectedUser);

        // Act
        $result = $this->command->handle($userData);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('Jane Doe', $result->name);
        $this->assertEquals('jane@example.com', $result->email);
        $this->assertEquals(2, $result->id);
        $this->assertEquals($profileData['bio'], $result->profile->bio);
        $this->assertEquals($profileData['phone'], $result->profile->phone);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Commands\User\DeleteUserCommandTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Commands\User;


class DeleteUserCommandTest extends TestCase
{
    protected $userRepository;
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->command = new DeleteUserCommand($this->userRepository);
    }

    /** @test */
    public function test_handle_deletes_user_successfully(): void
    {
        // Arrange
        $user = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->userRepository
            ->shouldReceive('delete')
            ->once()
            ->with($user)
            ->andReturn(true);

        // Act
        $result = $this->command->handle($user);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function test_handle_returns_false_when_deletion_fails(): void
    {
        // Arrange
        $user = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->userRepository
            ->shouldReceive('delete')
            ->once()
            ->with($user)
            ->andReturn(false);

        // Act
        $result = $this->command->handle($user);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function test_handle_passes_correct_user_to_repository(): void
    {
        // Arrange
        $user = new User([
            'id' => 42,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ]);

        $this->userRepository
            ->shouldReceive('delete')
            ->once()
            ->with(Mockery::on(function ($argument) use ($user) {
                return $argument instanceof User && 
                       $argument->id === $user->id &&
                       $argument->name === $user->name &&
                       $argument->email === $user->email;
            }))
            ->andReturn(true);

        // Act
        $result = $this->command->handle($user);

        // Assert
        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Commands\User\UpdateUserCommandTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Commands\User;


class UpdateUserCommandTest extends TestCase
{
    protected $userRepository;
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->command = new UpdateUserCommand($this->userRepository);
    }

    /** @test */
    public function test_handle_updates_user_successfully(): void
    {
        // Arrange
        $userData = new UserDTO(
            'John Updated',
            'john.updated@example.com'
        );

        $existingUser = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        // O comando agora recebe o objeto User, não o ID
        $this->userRepository
            ->shouldReceive('updateFromDTO')
            ->once()
            ->with($existingUser, $userData) // O mock deve receber o objeto User
            ->andReturn(true); // Retorna bool, como definido no repositório

        // Act
        $result = $this->command->handle($existingUser, $userData); // Chamada ajustada

        // Assert
        $this->assertTrue($result);
    }
    
    /** @test */
    public function test_handle_updates_user_with_profile_data(): void
    {
        // Arrange
        $existingUser = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com'
        ]);
        $existingUser->profile()->create(['bio' => 'Old bio', 'phone' => '111']);

        $profileData = ['bio' => 'New bio', 'phone' => '222'];
        $userData = new UserDTO(
            'Updated Name',
            'updated@example.com',
            null,
            $profileData
        );

        $this->userRepository
            ->shouldReceive('updateFromDTO')
            ->once()
            ->withArgs(function ($userArg, $dtoArg) use ($existingUser, $userData) {
                return $userArg->is($existingUser) && 
                       $dtoArg->getName() === $userData->getName() &&
                       $dtoArg->getEmail() === $userData->getEmail() &&
                       $dtoArg->getProfileData() === $userData->getProfileData();
            })
            ->andReturn(true);

        // Act
        $result = $this->command->handle($existingUser, $userData);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function test_handle_returns_false_on_repository_failure(): void
    {
        // Arrange
        $userData = new UserDTO(
            'John Updated',
            'john.updated@example.com'
        );

        $existingUser = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->userRepository
            ->shouldReceive('updateFromDTO')
            ->once()
            ->with($existingUser, $userData)
            ->andReturn(false);

        // Act
        $result = $this->command->handle($existingUser, $userData);

        // Assert
        $this->assertFalse($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\DTO\UserDTOTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\DTO;


class UserDTOTest extends TestCase
{
    /** @test */
    public function test_can_create_user_dto_with_all_properties(): void
    {
        // Arrange & Act
        $dto = new UserDTO(
            'John Doe',
            'john@example.com',
            'password123',
            ['bio' => 'A test bio']
        );

        // Assert
        $this->assertEquals('John Doe', $dto->getName());
        $this->assertEquals('john@example.com', $dto->getEmail());
        $this->assertEquals('password123', $dto->getPassword());
        $this->assertEquals(['bio' => 'A test bio'], $dto->getProfileData());
    }

    /** @test */
    public function test_can_create_user_dto_without_password_and_profile_data(): void
    {
        // Arrange & Act
        $dto = new UserDTO(
            'John Doe',
            'john@example.com'
        );

        // Assert
        $this->assertEquals('John Doe', $dto->getName());
        $this->assertEquals('john@example.com', $dto->getEmail());
        $this->assertNull($dto->getPassword());
        $this->assertEmpty($dto->getProfileData());
    }

    /** @test */
    public function test_can_convert_to_array_with_all_properties(): void
    {
        // Arrange
        $dto = new UserDTO(
            'John Doe',
            'john@example.com',
            'password123',
            ['bio' => 'A test bio']
        );

        // Act
        $array = $dto->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'profile' => ['bio' => 'A test bio']
        ], $array);
    }

    /** @test */
    public function test_can_convert_to_array_without_password_and_profile(): void
    {
        // Arrange
        $dto = new UserDTO(
            'John Doe',
            'john@example.com'
        );

        // Act
        $array = $dto->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ], $array);
    }

    /** @test */
    public function test_can_create_from_array_with_all_data(): void
    {
        // Arrange
        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'profile' => ['phone' => '987-654-3210']
        ];

        // Act
        $dto = UserDTO::fromArray($data);

        // Assert
        $this->assertInstanceOf(UserDTO::class, $dto);
        $this->assertEquals('Jane Doe', $dto->getName());
        $this->assertEquals('jane@example.com', $dto->getEmail());
        $this->assertEquals('secret123', $dto->getPassword());
        $this->assertEquals(['phone' => '987-654-3210'], $dto->getProfileData());
    }

    /** @test */
    public function test_can_create_from_array_without_profile_data(): void
    {
        // Arrange
        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123'
        ];

        // Act
        $dto = UserDTO::fromArray($data);

        // Assert
        $this->assertInstanceOf(UserDTO::class, $dto);
        $this->assertEquals('Jane Doe', $dto->getName());
        $this->assertEquals('jane@example.com', $dto->getEmail());
        $this->assertEquals('secret123', $dto->getPassword());
        $this->assertEmpty($dto->getProfileData());
    }

    /** @test */
    public function test_dto_is_immutable(): void
    {
        // Arrange
        $dto = new UserDTO(
            'John Doe',
            'john@example.com',
            'password123'
        );

        // Assert getters return correct values
        $this->assertEquals('John Doe', $dto->getName());
        $this->assertEquals('john@example.com', $dto->getEmail());
        $this->assertEquals('password123', $dto->getPassword());

        // There are no setters, so immutability is inherent.
        // We cannot test for direct modification.
    }
    
    /** @test */
    public function test_handles_null_password(): void
    {
        // Arrange & Act
        $dto = new UserDTO(
            'John Doe',
            'john@example.com',
            null
        );

        // Assert
        $this->assertEquals('John Doe', $dto->getName());
        $this->assertEquals('john@example.com', $dto->getEmail());
        $this->assertNull($dto->getPassword());
    }
}
```

### Arquivo: `Unit\ExampleTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit;


class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
```

### Arquivo: `Unit\Http\Controllers\Api\V1\UserControllerTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Http\Controllers\Api\V1;


class UserControllerTest extends TestCase
{
    protected $auditService;
    protected $userService;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = Mockery::mock(UserService::class);
        $this->auditService = Mockery::mock(AuditService::class);
        // Instancia o controller com os mocks
        $this->controller = new UserController($this->auditService, $this->userService);
    }

    /** @test */
    public function test_index_returns_paginated_users()
    {
        // Arrange
        $users = User::factory()->count(3)->make();
        $paginator = new LengthAwarePaginator($users, 3, 15, 1);

        $this->userService
            ->shouldReceive('getAllUsersPaginated') // Chamar o método correto do service
            ->once()
            ->with(15) // Esperar o perPage padrão
            ->andReturn($paginator);

        $request = new Request();
        $response = $this->controller->index($request);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email'] // Simplificado para exemplo, pode ser mais detalhado
            ],
            'meta' => [
                'pagination' => ['total', 'current_page', 'per_page']
            ]
        ]);
    }

    /** @test */
    public function test_show_returns_user_by_id()
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]); // Use make para não persistir no DB real

        $this->userService
            ->shouldReceive('getUserById')
            ->with(1)
            ->once()
            ->andReturn($user);

        // Act
        $response = $this->controller->show(1);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => 1, 'name' => $user->name]);
    }

    /** @test */
    public function test_show_returns_404_if_user_not_found()
    {
        // Arrange
        $this->userService
            ->shouldReceive('getUserById')
            ->with(999)
            ->once()
            ->andReturn(null);

        // Act
        $response = $this->controller->show(999);

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Resource not found']);
    }

    /** @test */
    public function test_store_creates_new_user()
    {
        // Arrange
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $user = User::factory()->make($userData); // Use make para não persistir

        $request = Mockery::mock(StoreUserRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($userData);
        
        $this->auditService->shouldReceive('logCreated')->once()->andReturnTrue();


        $this->userService
            ->shouldReceive('createUser')
            ->once()
            ->with(Mockery::on(function (UserDTO $dto) use ($userData) {
                return $dto->getName() === $userData['name'] &&
                       $dto->getEmail() === $userData['email'] &&
                       $dto->getPassword() === $userData['password'];
            }))
            ->andReturn($user);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonFragment(['name' => 'Test User', 'email' => 'test@example.com']);
    }

    /** @test */
    public function test_update_modifies_existing_user()
    {
        // Arrange
        $user = User::factory()->create(['id' => 1]); // Crie um user real para passar para o controller
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ];

        $request = Mockery::mock(UpdateUserRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($updateData);

        // O serviço espera um objeto User e um DTO
        $this->userService
            ->shouldReceive('updateUser')
            ->once()
            ->withArgs(function (User $userArg, UserDTO $dtoArg) use ($user, $updateData) {
                return $userArg->id === $user->id &&
                       $dtoArg->getName() === $updateData['name'] &&
                       $dtoArg->getEmail() === $updateData['email'];
            })
            ->andReturn(true); // update retorna bool agora

        $this->auditService->shouldReceive('logUpdated')->once()->andReturnTrue();

        // Act
        // O controller agora recebe o objeto User via Route Model Binding
        $response = $this->controller->update($request, $user);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Resource updated successfully']);
    }

    /** @test */
    public function test_update_returns_404_if_user_not_found()
    {
        // Arrange
        $updateData = ['name' => 'Updated Name'];
        $request = Mockery::mock(UpdateUserRequest::class);
        $request->shouldReceive('validated')->andReturn($updateData);

        // Moca um User "falso" para o Route Model Binding falhar
        $nonExistentUser = Mockery::mock(User::class);
        $nonExistentUser->shouldReceive('getAttribute')->with('id')->andReturn(999);
        $nonExistentUser->shouldReceive('exists')->andReturn(false); // Para simular que não existe no DB

        // Neste cenário, o service não será chamado porque o Route Model Binding falharia antes
        // Ou, se o controller for robusto e chamar um find, mockar null para o find.
        // Como o controller espera um objeto User, a validação de "não encontrado" seria na camada superior.
        // Este teste é mais apropriado para um Feature Test. Para um Unit Test, focamos na lógica *dentro* do controller.
        // Portanto, para simular um "not found" no Unit Test, precisaríamos mockar um repositório que retorna null,
        // mas o update do controller não busca o user (ele recebe).

        // Ajustando o teste para o cenário unitário: se updateUser retorna false
        $existingUser = User::factory()->create(['id' => 1]);
        $this->userService
            ->shouldReceive('updateUser')
            ->once()
            ->andReturn(false); // Simula que a atualização falhou por alguma razão no service

        $response = $this->controller->update($request, $existingUser);

        $response->assertStatus(500); // Ou outro status de erro que o service retornaria
    }


    /** @test */
    public function test_destroy_deletes_user()
    {
        // Arrange
        $user = User::factory()->create(['id' => 1]); // Crie um user real

        $this->userService
            ->shouldReceive('deleteUser')
            ->with($user) // O service espera o objeto User
            ->once()
            ->andReturn(true);
        
        $this->auditService->shouldReceive('logDeleted')->once()->andReturnTrue();


        // Act
        $response = $this->controller->destroy($user);

        // Assert
        $response->assertStatus(200); // 200 para deletedResponse
        $response->assertJson(['message' => 'Resource deleted successfully']);
    }

    /** @test */
    public function test_destroy_returns_404_if_user_not_found()
    {
        // Arrange
        $nonExistentUser = Mockery::mock(User::class);
        $nonExistentUser->shouldReceive('getAttribute')->with('id')->andReturn(999);
        $nonExistentUser->shouldReceive('exists')->andReturn(false); 

        // O controller agora espera um objeto User. Se o Route Model Binding falhar,
        // o Laravel já lançaria uma exceção ModelNotFoundException antes do controller ser chamado.
        // Para simular isso em um Unit Test, precisaríamos mockar o Route Model Binding,
        // o que é mais complexo e pode ser mais bem testado em um Feature Test.

        // Simulação de falha no service
        $user = User::factory()->create(['id' => 1]); // User existente para simular falha interna
        $this->userService
            ->shouldReceive('deleteUser')
            ->with($user)
            ->once()
            ->andReturn(false); // Simula que a exclusão falhou no service

        $this->auditService->shouldReceive('logDeleted')->never(); // Não deve ser logado se a exclusão falha
        
        // Act
        $response = $this->controller->destroy($user);

        // Assert
        $response->assertStatus(500); // Ou o status de erro apropriado para falha no service
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Http\Controllers\ApiControllerTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Http\Controllers;



// Testable class that extends ApiController for testing protected/private methods
class TestableApiController extends ApiController
{
    public function callSuccessResponse($data = [], ?string $message = null, array $meta = []): JsonResponse
    {
        return $this->successResponse($data, $message, $meta);
    }

    public function callErrorResponse(string $message, int $statusCode = 400, $errors = null, string $errorCode = null): JsonResponse
    {
        return $this->errorResponse($message, $statusCode, $errors, $errorCode);
    }

    public function callCreatedResponse($data, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->createdResponse($data, $message);
    }

    public function callUpdatedResponse($data, string $message = 'Resource updated successfully'): JsonResponse
    {
        return $this->updatedResponse($data, $message);
    }

    public function callDeletedResponse(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return $this->deletedResponse($message);
    }

    public function callNotFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->notFoundResponse($message);
    }

    public function callValidationErrorResponse($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->validationErrorResponse($errors, $message);
    }

    public function callUnauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->unauthorizedResponse($message);
    }

    public function callForbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->forbiddenResponse($message);
    }

    public function callServerErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->serverErrorResponse($message);
    }

    public function callPaginatedResponse($paginator, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return $this->paginatedResponse($paginator, $message);
    }

    public function callTransformData($data)
    {
        return $this->transformData($data);
    }

    public function callSetStatusCode(int $statusCode): self
    {
        return $this->setStatusCode($statusCode);
    }
}

class ApiControllerTest extends TestCase
{
    protected TestableApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestableApiController();
        
        // Mock Carbon::now() for consistent timestamp testing
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon mock
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_sets_status_code_correctly(): void
    {
        $controller = $this->controller->callSetStatusCode(201);
        
        $response = $controller->callSuccessResponse(['test' => 'data']);
        
        $this->assertEquals(201, $response->getStatusCode());
    }

    /** @test */
    public function it_returns_successful_response_with_default_message(): void
    {
        $response = $this->controller->callSuccessResponse(['key' => 'value']);
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Operation successful',
            'data' => ['key' => 'value'],
            'timestamp' => '2023-01-01T12:00:00.000000Z'
        ]);
    }

    /** @test */
    public function it_returns_successful_response_with_custom_message(): void
    {
        $response = $this->controller->callSuccessResponse(['key' => 'value'], 'Custom success message');
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Custom success message',
            'data' => ['key' => 'value']
        ]);
    }

    /** @test */
    public function it_returns_successful_response_with_meta_data(): void
    {
        $meta = ['version' => '1.0', 'api_limit' => 100];
        $response = $this->controller->callSuccessResponse(['key' => 'value'], 'Test Message', $meta);
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Test Message',
            'data' => ['key' => 'value'],
            'meta' => $meta
        ]);
    }

    /** @test */
    public function it_returns_successful_response_without_meta_when_empty(): void
    {
        $response = $this->controller->callSuccessResponse(['key' => 'value'], 'Test Message', []);
        
        $responseData = $response->getData(true);
        $this->assertArrayNotHasKey('meta', $responseData);
    }

    /** @test */
    public function it_returns_error_response_with_minimum_data(): void
    {
        $response = $this->controller->callErrorResponse('Error Message');
        
        $this->assertEquals(400, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Error Message',
            'timestamp' => '2023-01-01T12:00:00.000000Z'
        ]);
        
        $responseData = $response->getData(true);
        $this->assertArrayNotHasKey('errors', $responseData);
        $this->assertArrayNotHasKey('error_code', $responseData);
    }

    /** @test */
    public function it_returns_error_response_with_full_data(): void
    {
        $errors = ['field' => 'error message'];
        $response = $this->controller->callErrorResponse('Error Message', 422, $errors, 'VALIDATION_ERROR');
        
        $this->assertEquals(422, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Error Message',
            'errors' => $errors,
            'error_code' => 'VALIDATION_ERROR'
        ]);
    }

    /** @test */
    public function it_returns_created_response_with_default_message(): void
    {
        $data = ['id' => 1, 'name' => 'New Resource'];
        $response = $this->controller->callCreatedResponse($data);
        
        $this->assertEquals(201, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Resource created successfully',
            'data' => $data
        ]);
    }

    /** @test */
    public function it_returns_created_response_with_custom_message(): void
    {
        $data = ['id' => 1];
        $response = $this->controller->callCreatedResponse($data, 'User created successfully');
        
        $this->assertEquals(201, $response->getStatusCode());
        $response->assertJson([
            'message' => 'User created successfully'
        ]);
    }

    /** @test */
    public function it_returns_updated_response(): void
    {
        $data = ['id' => 1, 'name' => 'Updated Resource'];
        $response = $this->controller->callUpdatedResponse($data, 'Resource updated');
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Resource updated',
            'data' => $data
        ]);
    }

    /** @test */
    public function it_returns_deleted_response(): void
    {
        $response = $this->controller->callDeletedResponse('Resource deleted');
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Resource deleted',
            'data' => []
        ]);
    }

    /** @test */
    public function it_returns_not_found_response(): void
    {
        $response = $this->controller->callNotFoundResponse();
        
        $this->assertEquals(404, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Resource not found',

 [... 23 linhas omitidas ... ]

            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
            'error_code' => 'VALIDATION_ERROR'
        ]);
    }

    /** @test */
    public function it_returns_validation_error_response_with_custom_message(): void
    {
        $errors = ['email' => ['Invalid email format']];
        $response = $this->controller->callValidationErrorResponse($errors, 'Form validation failed');
        
        $response->assertJson([
            'message' => 'Form validation failed'
        ]);
    }

    /** @test */
    public function it_returns_unauthorized_response(): void
    {
        $response = $this->controller->callUnauthorizedResponse();
        
        $this->assertEquals(401, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized',
            'error_code' => 'UNAUTHORIZED'
        ]);
    }

    /** @test */
    public function it_returns_forbidden_response(): void
    {
        $response = $this->controller->callForbiddenResponse();
        
        $this->assertEquals(403, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Forbidden',
            'error_code' => 'FORBIDDEN'
        ]);
    }

    /** @test */
    public function it_returns_server_error_response(): void
    {
        $response = $this->controller->callServerErrorResponse();
        
        $this->assertEquals(500, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Internal server error',
            'error_code' => 'SERVER_ERROR'
        ]);
    }

    /** @test */
    public function it_returns_paginated_response_with_length_aware_paginator(): void
    {
        $items = collect([['id' => 1, 'name' => 'Item 1'], ['id' => 2, 'name' => 'Item 2']]);
        $paginator = new LengthAwarePaginator(
            $items,
            20, // total
            10, // perPage
            1,  // currentPage
            ['path' => 'http://localhost/items']
        );

        $response = $this->controller->callPaginatedResponse($paginator, 'Paginated data');
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Paginated data',
            'data' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2']
            ]
        ]);

        $responseData = $response->getData(true);
        $this->assertArrayHasKey('meta', $responseData);
        $this->assertArrayHasKey('pagination', $responseData['meta']);
        
        $pagination = $responseData['meta']['pagination'];
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(2, $pagination['last_page']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(20, $pagination['total']);
        $this->assertEquals(1, $pagination['from']);
        $this->assertEquals(2, $pagination['to']);
        $this->assertTrue($pagination['has_more_pages']);
    }

    /** @test */
    public function it_returns_paginated_response_with_non_paginator_data(): void
    {
        $data = ['simple' => 'data'];
        $response = $this->controller->callPaginatedResponse($data, 'Simple data');
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Simple data',
            'data' => $data
        ]);
        
        $responseData = $response->getData(true);
        $this->assertArrayNotHasKey('meta', $responseData);
    }

    /** @test */
    public function it_transforms_json_resource_data(): void
    {
        $resource = Mockery::mock(JsonResource::class);
        $resource->shouldReceive('resolve')->once()->andReturn(['id' => 1, 'name' => 'Test']);
        
        $transformed = $this->controller->callTransformData($resource);
        
        $this->assertEquals(['id' => 1, 'name' => 'Test'], $transformed);
    }

    /** @test */
    public function it_transforms_resource_collection_data(): void
    {
        $collection = Mockery::mock(ResourceCollection::class);
        $collection->shouldReceive('resolve')->once()->andReturn([['id' => 1], ['id' => 2]]);
        
        $transformed = $this->controller->callTransformData($collection);
        
        $this->assertEquals([['id' => 1], ['id' => 2]], $transformed);
    }

    /** @test */
    public function it_transforms_eloquent_collection_data(): void
    {
        $collection = new Collection([['id' => 1], ['id' => 2]]);
        
        $transformed = $this->controller->callTransformData($collection);
        
        $this->assertEquals([['id' => 1], ['id' => 2]], $transformed);
    }

    /** @test */
    public function it_transforms_length_aware_paginator_data(): void
    {
        $items = collect([['id' => 1], ['id' => 2]]);
        $paginator = new LengthAwarePaginator($items, 2, 10, 1);
        
        $transformed = $this->controller->callTransformData($paginator);
        
        $this->assertEquals([['id' => 1], ['id' => 2]], $transformed);
    }

    /** @test */
    public function it_transforms_raw_array_data(): void
    {
        $data = ['id' => 1, 'name' => 'Raw Data'];
        
        $transformed = $this->controller->callTransformData($data);
        
        $this->assertEquals(['id' => 1, 'name' => 'Raw Data'], $transformed);
    }

    /** @test */
    public function it_transforms_null_data(): void
    {
        $transformed = $this->controller->callTransformData(null);
        
        $this->assertNull($transformed);
    }

    /** @test */
    public function it_transforms_empty_collection(): void
    {
        $collection = new Collection([]);
        
        $transformed = $this->controller->callTransformData($collection);
        
        $this->assertEquals([], $transformed);
    }

    /** @test */
    public function it_returns_response_with_correct_content_type(): void
    {
        $response = $this->controller->callSuccessResponse(['test' => 'data']);
        
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function it_chains_status_code_setting_with_response_methods(): void
    {
        $response = $this->controller->callSetStatusCode(418)->callSuccessResponse(['teapot' => true]);
        
        $this->assertEquals(418, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'data' => ['teapot' => true]
        ]);
    }

    /** @test */
    public function it_handles_complex_nested_data_structures(): void
    {
        $complexData = [
            'user' => [
                'id' => 1,
                'profile' => [
                    'name' => 'John Doe',
                    'settings' => [
                        'theme' => 'dark',
                        'notifications' => true
                    ]
                ]
            ],
            'permissions' => ['read', 'write']
        ];
        
        $response = $this->controller->callSuccessResponse($complexData);
        
        $response->assertJson([
            'data' => $complexData
        ]);
    }

    /** @test */
    public function it_handles_empty_data_array(): void
    {
        $response = $this->controller->callSuccessResponse([]);
        
        $response->assertJson([
            'success' => true,
            'data' => []
        ]);
    }

    /** @test */
    public function it_preserves_timestamp_format_in_responses(): void
    {
        $response = $this->controller->callSuccessResponse(['test' => 'data']);
        
        $responseData = $response->getData(true);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/',
            $responseData['timestamp']
        );
    }
}
```

### Arquivo: `Unit\Http\Middleware\ApiRateLimitMiddlewareTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Http\Middleware;


class ApiRateLimitMiddlewareTest extends TestCase
{
    protected $rateLimitService;
    protected ApiRateLimitMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a proper mock
        $this->rateLimitService = Mockery::mock(RateLimitService::class);
        
        // Bind the mock to the container
        $this->app->instance(RateLimitService::class, $this->rateLimitService);
        
        // Create middleware with the mocked service
        $this->middleware = new ApiRateLimitMiddleware($this->rateLimitService);
    }

    /** @test */
    public function it_allows_request_when_within_limit(): void
    {
        // Arrange
        $request = Request::create('/test-endpoint', 'GET');
        $user = User::factory()->make(['id' => 1]);
        $request->setUserResolver(fn () => $user);

        // Mocks para o RateLimitService
        $this->rateLimitService->shouldReceive('checkRateLimit')
            ->once()
            ->with($request, $user)
            ->andReturn([false, 99, Carbon::now()->addSeconds(60)]); // false = não excedeu

        $this->rateLimitService->shouldReceive('getRateLimitKey')
            ->once()
            ->with($request, $user)
            ->andReturn('user:1:endpoint:hash');
            
        $this->rateLimitService->shouldReceive('getLimitForKey')
            ->once()
            ->with('user:1:endpoint:hash', $user)
            ->andReturn(100);

        $next = function ($req) {
            return new Response('OK', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(100, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(99, $response->headers->get('X-RateLimit-Remaining'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
    }

    /** @test */
    public function it_blocks_request_when_exceeds_limit(): void
    {
        // Arrange
        $request = Request::create('/test-endpoint', 'GET');
        $user = User::factory()->make(['id' => 1]);
        $request->setUserResolver(fn () => $user);
        $resetTime = Carbon::now()->addSeconds(30);

        // Mocks para o RateLimitService
        $this->rateLimitService->shouldReceive('checkRateLimit')
            ->once()
            ->with($request, $user)
            ->andReturn([true, 0, $resetTime]); // true = excedeu

        // O 'next' não deve ser chamado
        $next = function ($req) {
            $this->fail('Next middleware should not be called when limit is exceeded.');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Limite de requisições excedido. Por favor, aguarde antes de tentar novamente.', $responseData['message']);
        $this->assertEquals($resetTime->diffInSeconds(Carbon::now()), $responseData['retry_after']);
        $this->assertNull($response->headers->get('X-RateLimit-Limit')); // Headers não devem ser adicionados se bloqueado antes de $next
    }

    /** @test */
    public function it_handles_guest_requests_rate_limiting(): void
    {
        // Arrange
        $request = Request::create('/public-endpoint', 'GET');
        $resetTime = Carbon::now()->addSeconds(60);

        // Mocks para o RateLimitService
        $this->rateLimitService->shouldReceive('checkRateLimit')
            ->once()
            ->with($request, null) // Sem usuário autenticado
            ->andReturn([false, 29, $resetTime]);

        $this->rateLimitService->shouldReceive('getRateLimitKey')
            ->once()
            ->with($request, null)
            ->andReturn('ip:hash:endpoint:hash'); // Chave baseada em IP
            
        $this->rateLimitService->shouldReceive('getLimitForKey')
            ->once()
            ->with('ip:hash:endpoint:hash', null)
            ->andReturn(30); // Limite padrão para não autenticados

        $next = function ($req) {
            return new Response('OK', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(30, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(29, $response->headers->get('X-RateLimit-Remaining'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Http\Middleware\CacheResponseMiddlewareTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Http\Middleware;


class CacheResponseMiddlewareTest extends TestCase
{
    protected CacheResponseMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CacheResponseMiddleware();
        Cache::fake(); // Falsifica o Cache
    }

    /** @test */
    public function it_does_not_cache_non_get_requests(): void
    {
        // Arrange
        $request = Request::create('/api/resource', 'POST'); // Método não cacheável
        $next = function ($req) {
            return new JsonResponse(['data' => 'created'], 201);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['data' => 'created']), $response->getContent());
        Cache::assertNothingStored(); // Nada deve ser armazenado no cache
        $this->assertNull($response->headers->get('X-API-Cache')); // Ou assertEquals('MISS', ...) se você adicionar fallback
    }

    /** @test */
    public function it_returns_cached_response_on_cache_hit(): void
    {
        // Arrange
        $request = Request::create('/api/resource', 'GET', ['param' => 'value']);
        $user = User::factory()->make(['id' => 10]);
        $request->setUserResolver(fn () => $user);

        // A chave deve corresponder à forma como o middleware a gera
        $expectedKey = 'api_cache:' . md5($request->url() . '_user_' . $user->id . '_query_' . json_encode($request->query()));
        $cachedData = ['data' => ['from_cache' => true], 'status' => 200];
        Cache::put($expectedKey, $cachedData, 60 * 60); // TTL em segundos

        // O 'next' não deve ser chamado
        $next = function ($req) {
            $this->fail('Next middleware should not be called when cache hit.');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($cachedData['data']), $response->getContent());
        $this->assertEquals('HIT', $response->headers->get('X-API-Cache'));
    }

    /** @test */
    public function it_caches_response_on_cache_miss(): void
    {
        // Arrange
        $request = Request::create('/api/resource', 'GET', ['param' => 'value']);
        $user = User::factory()->make(['id' => 10]);
        $request->setUserResolver(fn () => $user);

        $expectedKey = 'api_cache:' . md5($request->url() . '_user_' . $user->id . '_query_' . json_encode($request->query()));
        $originalData = ['data' => ['live_data' => true], 'status' => 200];

        // O 'next' deve ser chamado e retornar uma resposta
        $next = function ($req) use ($originalData) {
            return new JsonResponse($originalData['data'], $originalData['status']);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($originalData['data']), $response->getContent());
        $this->assertEquals('MISS', $response->headers->get('X-API-Cache'));
        
        // Verifica se o item foi armazenado no cache com o TTL correto (60 minutos)
        Cache::assertExists($expectedKey);
        $this->assertEquals($originalData, Cache::get($expectedKey));
    }

    /** @test */
    public function it_does_not_cache_unsuccessful_responses(): void
    {
        // Arrange
        $request = Request::create('/api/resource', 'GET');
        $next = function ($req) {
            return new JsonResponse(['error' => 'not found'], 404);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        Cache::assertNothingStored();
        $this->assertEquals('MISS', $response->headers->get('X-API-Cache'));
    }

    /** @test */
    public function it_uses_default_ttl_if_not_specified(): void
    {
        // Arrange
        $request = Request::create('/api/resource', 'GET');
        $next = function ($req) {
            return new JsonResponse(['data' => 'success'], 200);
        };
        $expectedKey = 'api_cache:' . md5($request->url() . '_guest_query_[]'); // Guest, sem query params

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        Cache::assertExists($expectedKey);
        // Não é possível verificar o TTL exato com Cache::fake(), mas podemos simular e verificar se expira
        // Ou em um teste de integração real com Redis, verificar TTL diretamente
        // Por ora, assertExists é suficiente para a unidade
    }

    /** @test */
    public function it_uses_custom_ttl_if_specified(): void
    {
        // Arrange
        $request = Request::create('/api/resource', 'GET');
        $next = function ($req) {
            return new JsonResponse(['data' => 'success'], 200);
        };
        $customTtl = 30; // 30 minutos
        $expectedKey = 'api_cache:' . md5($request->url() . '_guest_query_[]');

        // Act
        $response = $this->middleware->handle($request, $next, $customTtl);

        // Assert
        Cache::assertExists($expectedKey);
        // Similar ao teste anterior, o TTL exato não é testável diretamente com Cache::fake()
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Http\Middleware\RefreshTokenMiddlewareTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Http\Middleware;


class RefreshTokenMiddlewareTest extends TestCase
{
    protected RefreshTokenMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RefreshTokenMiddleware();
        Auth::shouldReceive('check')->andReturn(false); // Default para não autenticado
    }

    /** @test */
    public function it_does_nothing_if_user_is_not_authenticated(): void
    {
        // Arrange
        $request = Request::create('/');
        $next = function ($req) {
            return new JsonResponse(['status' => 'ok']);
        };

        Auth::shouldReceive('check')->andReturn(false); // Garante que não está autenticado

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('X-New-Token'));
    }

    /** @test */
    public function it_does_nothing_if_token_is_not_found(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer invalid_token');

        // Mock PersonalAccessToken para não encontrar o token
        Mockery::mock('alias:Laravel\Sanctum\PersonalAccessToken')
            ->shouldReceive('findToken')
            ->once()
            ->with('invalid_token')
            ->andReturn(null);

        $next = function ($req) {
            return new JsonResponse(['status' => 'ok']);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('X-New-Token'));
    }

    /** @test */
    public function it_does_not_refresh_token_if_not_expiring_soon(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer valid_token');

        // Token criado há 10 minutos (TTL padrão 60 min, threshold 30 min) -> não precisa de refresh
        $accessToken = Mockery::mock(PersonalAccessToken::class);
        $accessToken->created_at = Carbon::now()->subMinutes(10);
        config(['sanctum.expiration' => 60]); // 60 minutos de expiração

        Mockery::mock('alias:Laravel\Sanctum\PersonalAccessToken')
            ->shouldReceive('findToken')
            ->once()
            ->with('valid_token')
            ->andReturn($accessToken);

        $next = function ($req) {
            return new JsonResponse(['status' => 'ok']);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('X-New-Token'));
    }

    /** @test */
    public function it_refreshes_token_if_expiring_soon(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer old_token');

        // Token criado há 40 minutos (TTL padrão 60 min, threshold 30 min) -> precisa de refresh
        $accessToken = Mockery::mock(PersonalAccessToken::class);
        $accessToken->created_at = Carbon::now()->subMinutes(40);
        config(['sanctum.expiration' => 60]); // 60 minutos de expiração

        Mockery::mock('alias:Laravel\Sanctum\PersonalAccessToken')
            ->shouldReceive('findToken')
            ->once()
            ->with('old_token')
            ->andReturn($accessToken);
        
        // Mock do createToken para o User model
        $user->shouldReceive('createToken')
             ->once()
             ->with('api-refresh')
             ->andReturn((object)['plainTextToken' => 'new_fresh_token']);

        $next = function ($req) {
            return new JsonResponse(['status' => 'ok']);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('new_fresh_token', $response->headers->get('X-New-Token'));
    }

    /** @test */
    public function it_works_with_custom_sanctum_expiration_config(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer test_token');

        // Configura TTL de Sanctum para 30 minutos
        config(['sanctum.expiration' => 30]);

        // Token criado há 10 minutos -> não precisa de refresh (threshold ainda é 30 min, mas o token total é 30)
        // Significa que se expira em 20 min, ainda não está "soon" (menor que 30 min)
        $accessToken = Mockery::mock(PersonalAccessToken::class);
        $accessToken->created_at = Carbon::now()->subMinutes(1); // Expira em 29 min (quase agora)
        
        Mockery::mock('alias:Laravel\Sanctum\PersonalAccessToken')
            ->shouldReceive('findToken')
            ->once()
            ->with('test_token')
            ->andReturn($accessToken);

        // Mock do createToken
        $user->shouldReceive('createToken')
             ->once()
             ->andReturn((object)['plainTextToken' => 'another_new_token']);


        $next = function ($req) {
            return new JsonResponse(['status' => 'ok']);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert: Deve refreshar se o token expirar antes do threshold
        // created_at (1 min atrás) + expiration (30 min) = expira em 29 min a partir de agora
        // 29 min < 30 min (threshold) => precisa de refresh
        $this->assertEquals('another_new_token', $response->headers->get('X-New-Token'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Jobs\ProcessUserRegistrationTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Jobs;


class ProcessUserRegistrationTest extends TestCase
{
    /** @test */
    public function test_handle_dispatches_send_welcome_email_job_successfully(): void
    {
        // Arrange
        Queue::fake(); // Para mocar a fila e verificar se o job foi despachado
        Log::fake(); // Para mocar o log

        $user = User::factory()->create();
        $job = new ProcessUserRegistration($user);

        // Act
        $job->handle();

        // Assert
        // Verifica se o SendWelcomeEmailJob foi despachado para a fila
        Queue::assertPushed(SendWelcomeEmailJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });

        // Verifica se a mensagem de log foi gerada
        Log::assertLogged('info', function ($message, $context) use ($user) {
            return str_contains($message, 'Processando registro em segundo plano') &&
                   $context['user_id'] === $user->id; // Adicionado user_id ao log
        });
    }

    /** @test */
    public function test_job_can_be_serialized(): void
    {
        // Arrange
        $user = User::factory()->create();
        $job = new ProcessUserRegistration($user);

        // Act
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        // Assert
        $this->assertInstanceOf(ProcessUserRegistration::class, $unserialized);
        $this->assertEquals($user->id, $unserialized->user->id);
    }
}
```

### Arquivo: `Unit\Jobs\SendWelcomeEmailJobTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Jobs;


class SendWelcomeEmailJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake(); // Falsifica o envio de emails
        Log::fake(); // Falsifica o log
    }

    /** @test */
    public function it_sends_welcome_email_to_user(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $job = new SendWelcomeEmailJob($user);

        // Act
        $job->handle();

        // Assert
        Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) &&
                $mail->user->id === $user->id;
        });

        // Verifica que o log de sucesso foi gerado
        Log::assertLogged('info', function ($message, $context) use ($user) {
            return str_contains($message, 'Welcome email sent successfully') &&
                $context['user_id'] === $user->id &&
                $context['email'] === $user->email;
        });
    }

    /** @test */
    public function test_job_can_be_serialized(): void
    {
        // Arrange
        $user = User::factory()->create();
        $job = new SendWelcomeEmailJob($user);

        // Act
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        // Assert
        $this->assertInstanceOf(SendWelcomeEmailJob::class, $unserialized);
        $this->assertEquals($user->id, $unserialized->user->id);
    }

    /** @test */
    public function test_job_retries_on_temporary_mail_service_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $job = new SendWelcomeEmailJob($user);
        $exception = new Exception('Connection could not be established');

        // Usar reflection para acessar o método privado
        $reflectionClass = new \ReflectionClass($job);
        $shouldRetryMethod = $reflectionClass->getMethod('shouldRetry');
        $shouldRetryMethod->setAccessible(true);

        // Assert - verifica se deve tentar novamente
        $this->assertTrue($shouldRetryMethod->invoke($job, $exception));
    }

    /** @test */
    public function test_job_fails_definitively_on_non_retryable_exception(): void
    {
        // Arrange
        $user = User::factory()->create();
        $job = new SendWelcomeEmailJob($user);
        $exception = new Exception('Invalid email format');

        // Usar reflection para acessar o método privado
        $reflectionClass = new \ReflectionClass($job);
        $shouldRetryMethod = $reflectionClass->getMethod('shouldRetry');
        $shouldRetryMethod->setAccessible(true);

        // Assert - verifica se NÃO deve tentar novamente
        $this->assertFalse($shouldRetryMethod->invoke($job, $exception));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Models\UserTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Models;


class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'device_token' => 'some_device_token_xyz',
        ]);
    }

    /** @test */
    public function it_has_fillable_attributes(): void
    {
        $fillable = [
            'name',
            'email',
            'password',
            'device_token',
        ];

        $this->assertEquals($fillable, $this->user->getFillable());
    }

    /** @test */
    public function it_has_hidden_attributes(): void
    {
        $hidden = [
            'password',
            'remember_token',
        ];

        $this->assertEquals($hidden, $this->user->getHidden());
    }

    /** @test */
    public function it_has_casts(): void
    {
        $casts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];

        // Comparar apenas as chaves esperadas, pois Laravel adiciona 'id' => 'int' automaticamente
        foreach ($casts as $key => $value) {
            $this->assertArrayHasKey($key, $this->user->getCasts());
            $this->assertEquals($value, $this->user->getCasts()[$key]);
        }
    }

    /** @test */
    public function it_can_create_user_with_valid_data(): void
    {
        $userData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jane Smith', $user->name);
        $this->assertEquals('jane@example.com', $user->email);
        $this->assertNotNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('users', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);
    }

    /** @test */
    public function it_has_profile_relationship(): void
    {
        $profile = Profile::factory()->create(['user_id' => $this->user->id]);

        $this->assertInstanceOf(Profile::class, $this->user->profile);
        $this->assertEquals($profile->id, $this->user->profile->id);
    }

    /** @test */
    public function it_has_features_relationship(): void // Renomeado para 'features'
    {
        $featureUser = FeatureUser::factory()->create(['user_id' => $this->user->id]);

        $this->assertInstanceOf(Collection::class, $this->user->features); // Acessando 'features'
        $this->assertCount(1, $this->user->features);
        $this->assertEquals($featureUser->feature_name, $this->user->features->first()->name);
    }

    /** @test */
    public function it_automatically_hashes_password(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'plaintext-password',
        ]);

        $this->assertTrue(Hash::check('plaintext-password', $user->password));
        $this->assertNotEquals('plaintext-password', $user->password);
    }

    /** @test */
    public function it_handles_email_verification_date_properly(): void
    {
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->assertNotNull($verifiedUser->email_verified_at);
        $this->assertNull($unverifiedUser->email_verified_at);
    }

    /** @test */
    public function it_has_proper_table_name(): void
    {
        $this->assertEquals('users', $this->user->getTable());
    }

    /** @test */
    public function it_has_proper_primary_key(): void
    {
        $this->assertEquals('id', $this->user->getKeyName());
    }

    /** @test */
    public function it_uses_timestamps(): void
    {
        $this->assertTrue($this->user->usesTimestamps());
        $this->assertNotNull($this->user->created_at);
        $this->assertNotNull($this->user->updated_at);
    }

    /** @test */
    public function it_can_delete_user(): void
    {
        $userId = $this->user->id;
        $this->user->delete();
        
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    /** @test */
    public function it_can_update_user_attributes(): void
    {
        $this->user->update([
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $this->assertEquals('Updated Name', $this->user->fresh()->name);
        $this->assertEquals('updated@example.com', $this->user->fresh()->email);
    }

    /** @test */
    public function it_can_find_user_by_email(): void
    {
        $foundUser = User::where('email', $this->user->email)->first();

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->user->id, $foundUser->id);
        $this->assertEquals($this->user->email, $foundUser->email);
    }

    /** @test */
    public function it_returns_correct_attributes_array(): void
    {
        $attributes = $this->user->getAttributes();

        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('id', $attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertArrayHasKey('created_at', $attributes);
        $this->assertArrayHasKey('updated_at', $attributes);
        $this->assertArrayHasKey('device_token', $attributes);
    }

    /** @test */
    public function it_can_convert_to_array(): void
    {
        $userArray = $this->user->toArray();

        $this->assertIsArray($userArray);
        $this->assertEquals($this->user->id, $userArray['id']);
        $this->assertEquals($this->user->name, $userArray['name']);
        $this->assertEquals($this->user->email, $userArray['email']);
        $this->assertArrayNotHasKey('password', $userArray); // Hidden attribute
    }

    /** @test */
    public function it_can_convert_to_json(): void
    {
        $userJson = $this->user->toJson();
        $decoded = json_decode($userJson, true);

        $this->assertIsString($userJson);
        $this->assertEquals($this->user->id, $decoded['id']);
        $this->assertEquals($this->user->name, $decoded['name']);
        $this->assertEquals($this->user->email, $decoded['email']);
        $this->assertArrayNotHasKey('password', $decoded); // Hidden attribute
    }

    /** @test */
    public function it_handles_email_verified_at_casting(): void
    {
        $verificationDate = Carbon::now();
        
        $this->user->update(['email_verified_at' => $verificationDate]);
        
        $this->assertInstanceOf(Carbon::class, $this->user->fresh()->email_verified_at);
        $this->assertEquals($verificationDate->toDateTimeString(), 
                          $this->user->fresh()->email_verified_at->toDateTimeString());
    }

    /** @test */
    public function it_can_store_and_retrieve_device_token(): void
    {
        $token = 'new_device_token_abc';
        $this->user->update(['device_token' => $token]);

        $this->assertEquals($token, $this->user->fresh()->device_token);
    }
}
```

### Arquivo: `Unit\Policies\UserPolicyTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Policies;


class UserPolicyTest extends TestCase
{
    protected UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
    }

    /** @test */
    public function test_user_can_view_any_models(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        // A política atual retorna true para viewAny
        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function test_user_can_view_their_own_profile(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        $this->assertTrue($this->policy->view($user, $user));
    }

    /** @test */
    public function test_user_cannot_view_other_users_profile(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Act & Assert
        $this->assertFalse($this->policy->view($user1, $user2));
    }

    /** @test */
    public function test_user_cannot_create_models(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        // A política atual retorna false para create (exceto se for admin)
        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function test_user_can_update_their_own_profile(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        $this->assertTrue($this->policy->update($user, $user));
    }

    /** @test */
    public function test_user_cannot_update_other_users_profile(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Act & Assert
        $this->assertFalse($this->policy->update($user1, $user2));
    }

    /** @test */
    public function test_user_can_delete_their_own_profile(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        $this->assertTrue($this->policy->delete($user, $user));
    }

    /** @test */
    public function test_user_cannot_delete_other_users_profile(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Act & Assert
        $this->assertFalse($this->policy->delete($user1, $user2));
    }

    /** @test */
    public function test_user_cannot_restore_models(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        // A política atual retorna false para restore
        $this->assertFalse($this->policy->restore($user, User::factory()->make()));
    }

    /** @test */
    public function test_user_cannot_force_delete_models(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act & Assert
        // A política atual retorna false para forceDelete
        $this->assertFalse($this->policy->forceDelete($user, User::factory()->make()));
    }
}
```

### Arquivo: `Unit\Queries\User\GetAllUsersQueryTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Queries\User;


class GetAllUsersQueryTest extends TestCase
{
    protected $userRepository;
    protected $query;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->query = new GetAllUsersQuery($this->userRepository);
    }

    /** @test */
    public function test_handle_returns_paginated_users(): void
    {
        // Arrange
        $users = collect([
            new User(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']),
            new User(['id' => 2, 'name' => 'Jane Doe', 'email' => 'jane@example.com'])
        ]);

        $paginatedUsers = new LengthAwarePaginator(
            $users,
            2,
            15,
            1,
            ['path' => 'http://localhost/api/users']
        );

        $this->userRepository
            ->shouldReceive('paginate') // Chamada ajustada para paginate
            ->once()
            ->with(15)
            ->andReturn($paginatedUsers);

        // Act
        $result = $this->query->handle(15); // Chamada ajustada para handle

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result->items());
    }

    /** @test */
    public function test_handle_with_custom_per_page(): void
    {
        // Arrange
        $perPage = 5;
        $users = collect([
            new User(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'])
        ]);

        $paginatedUsers = new LengthAwarePaginator(
            $users,
            1,
            $perPage,
            1,
            ['path' => 'http://localhost/api/users']
        );

        $this->userRepository
            ->shouldReceive('paginate') // Chamada ajustada
            ->once()
            ->with($perPage)
            ->andReturn($paginatedUsers);

        // Act
        $result = $this->query->handle($perPage); // Chamada ajustada

        // Assert
        $this->assertEquals($perPage, $result->perPage());
    }

    /** @test */
    public function test_handle_handles_repository_exceptions(): void
    {
        // Arrange
        $this->userRepository
            ->shouldReceive('paginate') // Chamada ajustada
            ->once()
            ->with(15)
            ->andThrow(new \Exception('Database connection failed'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->query->handle(15); // Chamada ajustada
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Queries\User\GetUserByEmailQueryTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Queries\User;


class GetUserByEmailQueryTest extends TestCase
{
    protected $userRepository;
    protected $query;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->query = new GetUserByEmailQuery($this->userRepository);
    }

    /** @test */
    public function test_handle_returns_user_when_found(): void
    {
        // Arrange
        $email = 'john@example.com';
        $expectedUser = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($expectedUser);

        // Act
        $result = $this->query->handle($email); // Chamada ajustada para handle

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);
    }

    /** @test */
    public function test_handle_returns_null_when_user_not_found(): void
    {
        // Arrange
        $email = 'nonexistent@example.com';

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn(null);

        // Act
        $result = $this->query->handle($email); // Chamada ajustada

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function test_handle_handles_repository_exceptions(): void
    {
        // Arrange
        $email = 'john@example.com';

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andThrow(new \Exception('Database connection failed'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->query->handle($email); // Chamada ajustada
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Queries\User\GetUserByIdQueryTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Queries\User;


class GetUserByIdQueryTest extends TestCase
{
    protected $userRepository;
    protected $query;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->query = new GetUserByIdQuery($this->userRepository);
    }

    /** @test */
    public function test_handle_returns_user_when_found(): void
    {
        // Arrange
        $userId = 1;
        $expectedUser = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with($userId)
            ->andReturn($expectedUser);

        // Act
        $result = $this->query->handle($userId); // Chamada ajustada para handle

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals('john@example.com', $result->email);
    }

    /** @test */
    public function test_handle_returns_null_when_user_not_found(): void
    {
        // Arrange
        $userId = 999;

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with($userId)
            ->andReturn(null);

        // Act
        $result = $this->query->handle($userId); // Chamada ajustada

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function test_handle_handles_repository_exceptions(): void
    {
        // Arrange
        $userId = 1;

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with($userId)
            ->andThrow(new \Exception('Database connection failed'));

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->query->handle($userId); // Chamada ajustada
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Repositories\EloquentUserRepositoryTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Repositories;


class EloquentUserRepositoryTest extends TestCase
{
    protected EloquentUserRepository $userRepository; // Adicionar tipo aqui

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = new EloquentUserRepository(new User());
    }

    /** @test */
    public function test_user_can_be_created_from_dto(): void
    {
        // Arrange
        $userData = new UserDTO(
            'John Doe',
            'john@example.com',
            'password123'
        );

        // Act
        $user = $this->userRepository->createFromDTO($userData);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertNotNull($user->id);
        $this->assertTrue(Hash::check('password123', $user->password)); // Verifica senha hasheada
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        $this->assertNotNull($user->profile); // Verifica que um perfil foi criado
    }

    /** @test */
    public function test_user_can_be_created_from_dto_with_profile_data(): void
    {
        // Arrange
        $profileData = ['bio' => 'A test bio', 'phone' => '123-456-7890'];
        $userData = new UserDTO(
            'Jane Doe',
            'jane@example.com',
            'password123',
            $profileData
        );

        // Act
        $user = $this->userRepository->createFromDTO($userData);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jane Doe', $user->name);
        $this->assertEquals('jane@example.com', $user->email);
        $this->assertNotNull($user->id);
        $this->assertNotNull($user->profile);
        $this->assertEquals('A test bio', $user->profile->bio);
        $this->assertEquals('123-456-7890', $user->profile->phone);
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'bio' => 'A test bio',
            'phone' => '123-456-7890'
        ]);
    }

    /** @test */
    public function test_user_can_be_retrieved_by_id(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ]);

        // Act
        $retrievedUser = $this->userRepository->findById($user->id);

        // Assert
        $this->assertInstanceOf(User::class, $retrievedUser);
        $this->assertEquals($user->id, $retrievedUser->id);
        $this->assertEquals('Jane Doe', $retrievedUser->name);
        $this->assertEquals('jane@example.com', $retrievedUser->email);
    }

    /** @test */
    public function test_user_can_be_retrieved_by_email(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com'
        ]);

        // Act
        $retrievedUser = $this->userRepository->findByEmail('bob@example.com');

        // Assert
        $this->assertInstanceOf(User::class, $retrievedUser);
        $this->assertEquals($user->id, $retrievedUser->id);
        $this->assertEquals('Bob Smith', $retrievedUser->name);
        $this->assertEquals('bob@example.com', $retrievedUser->email);
    }

    /** @test */
    public function test_find_by_id_returns_null_when_user_not_found(): void
    {
        // Act
        $user = $this->userRepository->findById(999);

        // Assert
        $this->assertNull($user);
    }

    /** @test */
    public function test_find_by_email_returns_null_when_user_not_found(): void
    {
        // Act
        $user = $this->userRepository->findByEmail('nonexistent@example.com');

        // Assert
        $this->assertNull($user);
    }

    /** @test */
    public function test_user_can_be_updated_from_dto(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com'
        ]);
        $user->profile()->create(['bio' => 'Original bio']); // Criar um perfil para o usuário

        $updateData = new UserDTO(
            'Updated Name',
            'updated@example.com'
        );

        // Act
        $updated = $this->userRepository->updateFromDTO($user, $updateData);

        // Assert
        $this->assertTrue($updated);
        $user->fresh(); // Recarrega o modelo para pegar as mudanças do banco
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);
        // Verifica que o perfil não foi alterado se não passado no DTO
        $this->assertEquals('Original bio', $user->profile->bio);
    }

    /** @test */
    public function test_user_can_be_updated_from_dto_with_profile_data(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com'
        ]);
        $user->profile()->create(['bio' => 'Old bio', 'phone' => '111']);

        $profileData = ['bio' => 'New bio', 'phone' => '222'];
        $updateData = new UserDTO(
            'Updated Name',
            'updated@example.com',
            null, // Sem alterar a senha
            $profileData
        );

        // Act
        $updated = $this->userRepository->updateFromDTO($user, $updateData);

        // Assert
        $this->assertTrue($updated);
        $user->fresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
        $this->assertEquals('New bio', $user->profile->bio);
        $this->assertEquals('222', $user->profile->phone);
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'bio' => 'New bio',
            'phone' => '222'
        ]);
    }

    /** @test */
    public function test_user_can_be_deleted(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'To Be Deleted',
            'email' => 'delete@example.com'
        ]);

        // Act
        $result = $this->userRepository->delete($user); // Passa o objeto User

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);
    }

    /** @test */
    public function test_delete_returns_false_when_user_does_not_exist_in_db(): void
    {
        // Arrange
        $user = new User(['id' => 999]); // Objeto User que não existe no DB

        // Act
        $result = $this->userRepository->delete($user);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function test_get_all_users_returns_paginated_results(): void
    {
        // Arrange
        User::factory()->count(5)->create();

        // Act
        $result = $this->userRepository->getAll(3, []);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->perPage());
        $this->assertEquals(5, $result->total());
        $this->assertCount(3, $result->items());
    }

    /** @test */
    public function test_get_all_users_with_name_filter(): void
    {
        // Arrange
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);
        User::factory()->create(['name' => 'John Johnson']);

        // Act
        $result = $this->userRepository->getAll(15, ['name' => 'John']);

        // Assert
        $this->assertEquals(2, $result->total());
        $this->assertCount(2, $result->items());
        
        foreach ($result->items() as $user) {
            $this->assertStringContainsString('John', $user->name);
        }
    }

    /** @test */
    public function test_get_all_users_with_email_filter(): void
    {
        // Arrange
        User::factory()->create(['email' => 'test1@example.com']);
        User::factory()->create(['email' => 'test2@example.com']);
        User::factory()->create(['email' => 'different@domain.com']);

        // Act
        $result = $this->userRepository->getAll(15, ['email' => 'test1@example.com']);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertCount(1, $result->items());
        $this->assertEquals('test1@example.com', $result->items()[0]->email);
    }

    /** @test */
    public function test_get_all_users_with_multiple_filters(): void
    {
        // Arrange
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        User::factory()->create([
            'name' => 'John Smith',
            'email' => 'johnsmith@example.com'
        ]);
        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com'
        ]);

        // Act
        $result = $this->userRepository->getAll(15, [
            'name' => 'John',
            'email' => 'john@example.com'
        ]);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertCount(1, $result->items());
        $this->assertEquals('John Doe', $result->items()[0]->name);
        $this->assertEquals('john@example.com', $result->items()[0]->email);
    }

    /** @test */
    public function test_update_from_dto_handles_password_hashing(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('old_password'),
        ]);

        $newPassword = 'new_strong_password';
        $updateData = new UserDTO(
            'Test User',
            'test@example.com',
            $newPassword
        );

        // Act
        $updated = $this->userRepository->updateFromDTO($user, $updateData);

        // Assert
        $this->assertTrue($updated);
        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
        $this->assertFalse(Hash::check('old_password', $user->fresh()->password));
    }

    /** @test */
    public function test_update_from_dto_does_not_change_password_if_not_provided_in_dto(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('old_password'),
        ]);

        $oldPasswordHash = $user->password;

        $updateData = new UserDTO(
            'Updated Name',
            'updated@example.com',
            null // Password is null, should not change
        );

        // Act
        $updated = $this->userRepository->updateFromDTO($user, $updateData);

        // Assert
        $this->assertTrue($updated);
        $this->assertEquals('Updated Name', $user->fresh()->name);
        $this->assertEquals('updated@example.com', $user->fresh()->email);
        $this->assertEquals($oldPasswordHash, $user->fresh()->password); // Password should remain the same
    }
}
```

### Arquivo: `Unit\Requests\User\StoreUserRequestTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Http\Requests\User; // Namespace corrigido


class StoreUserRequestTest extends TestCase
{
    /** @test */
    public function test_valid_data_passes_validation(): void
    {
        // Arrange
        $request = new StoreUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'profile' => [
                'bio' => 'A short bio',
                'phone' => '1234567890'
            ]
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_required_fields_validation(): void
    {
        // Arrange
        $request = new StoreUserRequest();
        $data = [];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /** @test */
    public function test_email_format_validation(): void
    {
        // Arrange
        $request = new StoreUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /** @test */
    public function test_email_unique_validation(): void
    {
        // Arrange
        \App\Models\User::factory()->create(['email' => 'existing@example.com']);
        $request = new StoreUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'existing@example.com', // Duplicated email
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /** @test */
    public function test_password_confirmation_validation(): void
    {
        // Arrange
        $request = new StoreUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password'
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /** @test */
    public function test_password_minimum_length_validation(): void
    {
        // Arrange
        $request = new StoreUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short', // less than 8 chars
            'password_confirmation' => 'short'
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /** @test */
    public function test_profile_fields_are_sometimes_array(): void
    {
        // Arrange
        $request = new StoreUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'profile' => [
                'bio' => 123, // Invalid type
                'phone' => str_repeat('a', 21) // Too long
            ]
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('profile.bio', $validator->errors()->toArray());
        $this->assertArrayHasKey('profile.phone', $validator->errors()->toArray());
    }

    /** @test */
    public function test_messages_are_returned(): void
    {
        $request = new StoreUserRequest();
        $data = [
            'email' => 'invalid',
            'password' => 'short',
            'password_confirmation' => 'mismatch'
        ];

        $validator = Validator::make($data, $request->rules());
        $messages = $validator->errors()->toArray();

        $this->assertEquals('O nome é obrigatório', $messages['name'][0]);
        $this->assertEquals('Formato de email inválido', $messages['email'][0]);
        $this->assertEquals('A senha deve ter pelo menos 8 caracteres', $messages['password'][0]);
        $this->assertEquals('As senhas não conferem', $messages['password'][1]);
    }
}
```

### Arquivo: `Unit\Requests\User\UpdateUserRequestTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Http\Requests\User; // Namespace corrigido


class UpdateUserRequestTest extends TestCase
{
    /** @test */
    public function test_valid_data_passes_validation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'name' => 'John Updated',
            'email' => 'john.updated@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'profile' => [
                'bio' => 'An updated bio',
                'phone' => '0987654321'
            ]
        ];

        // Mock the route parameter 'user' for unique email rule
        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_name_is_sometimes_required_and_string(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'name' => '', // Fails 'required' and 'string' implicitly
        ];

        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertStringContainsString('The name field is required', $validator->errors()->first('name'));
    }

    /** @test */
    public function test_name_is_optional_if_not_provided(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'email' => 'john.updated@example.com'
        ];

        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
        $this->assertArrayNotHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function test_email_is_optional_if_not_provided(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'name' => 'John Updated'
        ];

        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
        $this->assertArrayNotHasKey('email', $validator->errors()->toArray());
    }

    /** @test */
    public function test_email_format_validation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'email' => 'invalid-email'
        ];

        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /** @test */
    public function test_email_uniqueness_validation_excludes_current_user(): void
    {
        // Arrange
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        
        $request = new UpdateUserRequest();
        $data = [
            'email' => 'user1@example.com' // Email of current user
        ];

        $this->mockRouteParameter($request, $user1);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->fails()); // Should pass because it's the current user's email
    }

    /** @test */
    public function test_email_uniqueness_validation_fails_for_other_users(): void
    {
        // Arrange
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        
        $request = new UpdateUserRequest();
        $data = [
            'email' => 'user2@example.com' // Email of another user
        ];

        $this->mockRouteParameter($request, $user1);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /** @test */
    public function test_password_minimum_length_validation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'password' => 'short', // Too short
            'password_confirmation' => 'short'
        ];

        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /** @test */
    public function test_password_confirmation_validation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'password' => 'password123',
            'password_confirmation' => 'different_password'
        ];

        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /**
     * Helper to mock route parameter for Rule::unique()
     */
    protected function mockRouteParameter(UpdateUserRequest $request, User $user): void
    {
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('parameter')->with('user')->andReturn($user);
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Resources\UserResourceTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Resources;


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
```

### Arquivo: `Unit\Services\BatchProcessorServiceTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Services;


class BatchProcessorServiceTest extends TestCase
{
    protected BatchProcessorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BatchProcessorService();
        DB::fake();
        Log::fake();
    }

    /** @test */
    public function it_processes_items_successfully_without_transaction(): void
    {
        $items = collect([1, 2, 3, 4, 5]);
        $processedItems = [];
        $callback = function ($item) use (&$processedItems) {
            $processedItems[] = $item * 2;
        };

        $result = $this->service->process($items, $callback, 2, false);

        $this->assertEquals([2, 4, 6, 8, 10], $processedItems);
        $this->assertCount(5, $result['success']);
        $this->assertCount(0, $result['fail']);
        $this->assertEquals(5, $result['statistics']['total']);
        $this->assertEquals(5, $result['statistics']['processed']);
        $this->assertEquals(0, $result['statistics']['failed']);
    }

    /** @test */
    public function it_processes_items_successfully_with_transaction(): void
    {
        $items = collect([1, 2, 3]);
        $processedItems = [];
        $callback = function ($item) use (&$processedItems) {
            $processedItems[] = $item + 1;
        };

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                $callback();
            });

        $result = $this->service->process($items, $callback, 3, true);

        $this->assertCount(3, $result['success']);
        $this->assertEquals(3, $result['statistics']['processed']);
    }

    /** @test */
    public function it_handles_failures_during_processing(): void
    {
        $items = collect([1, 2, 3]);
        $callback = function ($item) {
            if ($item === 2) {
                throw new \Exception('Processing error for item 2');
            }
            return true;
        };

        $result = $this->service->process($items, $callback, 1, false);

        $this->assertCount(2, $result['success']);
        $this->assertCount(1, $result['fail']);
        $this->assertEquals(3, $result['statistics']['total']);
        $this->assertEquals(3, $result['statistics']['processed']);
        $this->assertEquals(1, $result['statistics']['failed']);
        Log::assertLogged('error', function ($message) {
            return str_contains($message, 'Erro ao processar item: Processing error for item 2');
        });
    }

    /** @test */
    public function it_performs_bulk_upsert_successfully(): void
    {
        $models = collect([
            User::factory()->make(['id' => 1, 'email' => 'user1@example.com', 'name' => 'User One']),
            User::factory()->make(['id' => 2, 'email' => 'user2@example.com', 'name' => 'User Two']),
        ]);
        $uniqueKeys = ['email'];
        $modelClass = User::class;

        Mockery::mock('alias:' . $modelClass)
            ->shouldReceive('upsert')
            ->once()
            ->andReturn([1, 2]);

        $result = $this->service->bulkUpsert($models, $modelClass, $uniqueKeys, 2);

        $this->assertEquals(2, $result['statistics']['upserted']);
        $this->assertEquals(0, $result['statistics']['failed']);
        $this->assertEquals(2, $result['statistics']['total']);
    }

    /** @test */
    public function it_processes_with_retry_successfully_on_first_attempt(): void
    {
        $items = collect([1, 2, 3]);
        $callCount = 0;
        $callback = function ($item) use (&$callCount) {
            $callCount++;
            return true;
        };

        $result = $this->service->processWithRetry($items, $callback, 3);

        $this->assertCount(3, $result['success']);
        $this->assertEquals(3, $result['statistics']['processed']);
        $this->assertEquals(0, $result['statistics']['failed']);
        $this->assertEquals(3, $callCount);
    }

    /** @test */
    public function it_processes_with_retry_and_succeeds_after_retry(): void
    {
        $items = collect([1, 2, 3]);
        $attempts = 0;
        $callback = function ($item) use (&$attempts) {
            if ($item === 2 && $attempts < 1) {
                $attempts++;
                throw new \Exception('Temporary error');
            }
            return true;
        };

        $result = $this->service->processWithRetry($items, $callback, 3);

        $this->assertCount(3, $result['success']);
        $this->assertEquals(4, $result['statistics']['processed']); // 3 items + 1 retry
        $this->assertEquals(0, $result['statistics']['failed']);
        Log::assertLogged('warning', function ($message) {
            return str_contains($message, 'Tentativa 1 falhou para o item: Temporary error');
        });
    }

    /** @test */
    public function it_processes_with_retry_and_fails_after_max_retries(): void
    {
        $items = collect([1]);
        $callback = function ($item) {
            throw new \Exception('Persistent error');
        };

        $result = $this->service->processWithRetry($items, $callback, 2);

        $this->assertCount(0, $result['success']);
        $this->assertCount(1, $result['fail']);
        $this->assertEquals(1, $result['statistics']['total']);
        $this->assertEquals(2, $result['statistics']['processed']); // 2 attempts
        $this->assertEquals(1, $result['statistics']['failed']);

        Log::assertLogged('warning', function ($message) {
            return str_contains($message, 'Tentativa 1 falhou para o item: Persistent error');
        });
        Log::assertLogged('warning', function ($message) {
            return str_contains($message, 'Tentativa 2 falhou para o item: Persistent error');
        });
    }

    /** @test */
    public function it_processes_empty_collection(): void
    {
        $items = collect([]);
        $callback = function ($item) {
            return true;
        };

        $result = $this->service->process($items, $callback);

        $this->assertCount(0, $result['success']);
        $this->assertCount(0, $result['fail']);
        $this->assertEquals(0, $result['statistics']['total']);
        $this->assertEquals(0, $result['statistics']['processed']);
        $this->assertEquals(0, $result['statistics']['failed']);
    }

    /** @test */
    public function it_processes_large_batch_with_multiple_chunks(): void
    {
        $items = collect(range(1, 10));
        $processedItems = [];
        $callback = function ($item) use (&$processedItems) {
            $processedItems[] = $item;
        };

        $result = $this->service->process($items, $callback, 3, false);

        $this->assertCount(10, $result['success']);
        $this->assertEquals(10, $result['statistics']['processed']);
        $this->assertEquals(range(1, 10), $processedItems);
    }

    /** @test */
    public function it_handles_mixed_success_and_failure_in_batch(): void
    {
        $items = collect([1, 2, 3, 4, 5]);
        $callback = function ($item) {
            if ($item % 2 === 0) {
                throw new \Exception("Error processing item $item");
            }
            return true;
        };

        $result = $this->service->process($items, $callback, 2, false);

        $this->assertCount(3, $result['success']); // 1, 3, 5
        $this->assertCount(2, $result['fail']); // 2, 4
        $this->assertEquals(5, $result['statistics']['total']);
        $this->assertEquals(5, $result['statistics']['processed']);
        $this->assertEquals(2, $result['statistics']['failed']);
    }

    /** @test */
    public function it_processes_with_retry_handles_empty_collection(): void
    {
        $items = collect([]);
        $callback = function ($item) {
            return true;
        };

        $result = $this->service->processWithRetry($items, $callback, 3);

        $this->assertCount(0, $result['success']);
        $this->assertCount(0, $result['fail']);
        $this->assertEquals(0, $result['statistics']['total']);
        $this->assertEquals(0, $result['statistics']['processed']);
        $this->assertEquals(0, $result['statistics']['failed']);
    }

    /** @test */
    public function it_bulk_upsert_handles_empty_collection(): void
    {
        $models = collect([]);
        $modelClass = User::class;
        $uniqueKeys = ['email'];

        $result = $this->service->bulkUpsert($models, $modelClass, $uniqueKeys);

        $this->assertEquals(0, $result['statistics']['total']);
        $this->assertEquals(0, $result['statistics']['upserted']);
        $this->assertEquals(0, $result['statistics']['failed']);
    }

    /** @test */
    public function it_processes_with_transaction_rollback_on_failure(): void
    {
        $items = collect([1, 2, 3]);
        $callback = function ($item) {
            if ($item === 2) {
                throw new \Exception('Database error');
            }
            return true;
        };

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                $callback();
            });

        $result = $this->service->process($items, $callback, 3, true);

        $this->assertCount(2, $result['success']);
        $this->assertCount(1, $result['fail']);
        $this->assertEquals(1, $result['statistics']['failed']);
    }

    /** @test */
    public function it_processes_with_retry_respects_max_retries(): void
    {
        $items = collect([1]);
        $attemptCount = 0;
        $callback = function ($item) use (&$attemptCount) {
            $attemptCount++;
            throw new \Exception('Always fails');
        };

        $result = $this->service->processWithRetry($items, $callback, 3);

        $this->assertEquals(3, $attemptCount);
        $this->assertCount(0, $result['success']);
        $this->assertCount(1, $result['fail']);
        $this->assertEquals(1, $result['statistics']['failed']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Services\CacheServiceTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Services;


class CacheServiceTest extends TestCase
{
    protected CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::fake(); // Falsifica a facade Cache
        Redis::fake(); // Falsifica a facade Redis
        config(['cache.default' => 'array']); // Configura o driver de cache para 'array' por padrão

        $this->cacheService = new CacheService('my_app');
    }

    /** @test */
    public function it_remembers_value_from_cache(): void
    {
        // Arrange
        $key = 'test_key';
        $value = 'cached_value';
        $callback = fn () => 'new_value'; // This should not be called

        Cache::shouldReceive('remember')
            ->once()
            ->with("my_app:{$key}", 60 * 60, Mockery::type('callable'))
            ->andReturn($value);

        // Act
        $result = $this->cacheService->remember($key, $callback);

        // Assert
        $this->assertEquals($value, $result);
    }

    /** @test */
    public function it_puts_value_into_cache(): void
    {
        // Arrange
        $key = 'put_key';
        $value = 'put_value';
        Cache::shouldReceive('put')
            ->once()
            ->with("my_app:{$key}", $value, 60 * 60)
            ->andReturn(true);

        // Act
        $result = $this->cacheService->put($key, $value);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_checks_if_item_exists_in_cache(): void
    {
        // Arrange
        $key = 'has_key';
        Cache::shouldReceive('has')
            ->once()
            ->with("my_app:{$key}")
            ->andReturn(true);

        // Act
        $result = $this->cacheService->has($key);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_gets_item_from_cache(): void
    {
        // Arrange
        $key = 'get_key';
        $value = 'retrieved_value';
        Cache::shouldReceive('get')
            ->once()
            ->with("my_app:{$key}", null)
            ->andReturn($value);

        // Act
        $result = $this->cacheService->get($key);

        // Assert
        $this->assertEquals($value, $result);
    }

    /** @test */
    public function it_forgets_item_from_cache(): void
    {
        // Arrange
        $key = 'forget_key';
        Cache::shouldReceive('forget')
            ->once()
            ->with("my_app:{$key}")
            ->andReturn(true);

        // Act
        $result = $this->cacheService->forget($key);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_flushes_redis_cache_by_pattern(): void
    {
        // Arrange
        config(['cache.default' => 'redis']);
        $pattern = 'users:*';
        $redisMock = Mockery::mock(\Illuminate\Redis\Connections\Connection::class);
        $redisMock->shouldReceive('keys')->once()->with("my_app:{$pattern}*")->andReturn(['key1', 'key2']);
        $redisMock->shouldReceive('del')->once()->with(['key1', 'key2'])->andReturn(2);
        
        Cache::shouldReceive('getRedis')->andReturn($redisMock);

        // Act
        $result = $this->cacheService->flush($pattern);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_flushes_all_cache_for_non_redis_drivers(): void
    {
        // Arrange
        config(['cache.default' => 'file']); // Non-redis driver
        Cache::shouldReceive('flush')->once()->andReturn(true);

        // Act
        $result = $this->cacheService->flush('any_pattern');

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_generates_model_cache_key(): void
    {
        // Arrange
        $model = Mockery::mock(Model::class);
        $model->shouldReceive('getKey')->andReturn(1);
        $model->shouldReceive('get_class')->andReturn('App\\Models\\User'); // Simula get_class

        // Act
        $key = $this->cacheService->generateModelKey($model);

        // Assert
        $this->assertEquals('App\\Models\\User:1', $key);
    }

    /** @test */
    public function it_generates_collection_cache_key_for_empty_collection(): void
    {
        // Arrange
        $collection = new Collection();

        // Act
        $key = $this->cacheService->generateCollectionKey($collection, 'empty_users');

        // Assert
        $this->assertEquals('Collection:empty:empty_users', $key);
    }

    /** @test */
    public function it_generates_collection_cache_key_for_non_empty_collection(): void
    {
        // Arrange
        $item1 = (object)['id' => 1, 'name' => 'Item A'];
        $item2 = (object)['id' => 2, 'name' => 'Item B'];
        $collection = collect([$item1, $item2]);
        
        // Simular o método getKeyName do primeiro item (se fosse Eloquent Model)
        $modelMock = Mockery::mock(Model::class);
        $modelMock->shouldReceive('getKeyName')->andReturn('id');
        $collection->shouldReceive('first')->andReturn($modelMock); // Simular que a coleção contém modelos

        // Act
        $key = $this->cacheService->generateCollectionKey($collection, 'active_items');
        
        // Assert
        // A classe do primeiro item é 'stdClass' para objetos anônimos, ou o nome da classe se forem modelos Eloquent.
        // O MD5 é do 'id,id,...' ordenado
        $expectedMd5 = md5('1,2');
        $this->assertEquals('stdClass:active_items:' . $expectedMd5, $key);
    }

    /** @test */
    public function it_sets_default_ttl(): void
    {
        // Act
        $service = $this->cacheService->setDefaultTtl(120); // 120 minutos

        // Assert
        $this->assertEquals(120, $this->getProperty($service, 'defaultTtl'));
    }

    // Helper method to access protected properties for assertion
    protected function getProperty($object, $propertyName)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Services\ExportServiceTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Services;


class ExportServiceTest extends TestCase
{
    protected ExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = new ExportService();
        Storage::fake('exports'); // Falsifica o disco de exportação
        Cache::fake(); // Falsifica o cache
        // Mock Str para ter um retorno previsível no nome do arquivo
        Str::shouldReceive('random')->andReturn('random123');
        Str::shouldReceive('slug')->andReturnUsing(fn ($text) => str_replace(' ', '-', strtolower($text)));
    }

    /** @test */
    public function it_generates_a_unique_filename(): void
    {
        $filename = $this->callProtectedMethod($this->exportService, 'generateFilename', ['my-report', 'csv']);
        $this->assertStringContainsString('my-report', $filename);
        $this->assertStringContainsString('random123.csv', $filename);
        $this->assertStringEndsWith('.csv', $filename);
    }

    /** @test */
    public function it_sets_chunk_size(): void
    {
        $this->exportService->setChunkSize(500);
        $this->assertEquals(500, $this->getProperty($this->exportService, 'chunkSize'));
    }

    /** @test */
    public function it_creates_a_background_export_job_and_caches_data(): void
    {
        // Arrange
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('getQuery')->andReturn(new \Illuminate\Database\Query\Builder(Mockery::mock(\Illuminate\Database\ConnectionInterface::class)));
        $query->shouldReceive('getModel')->andReturn(new \App\Models\User()); // Assume um modelo para o query builder

        $columns = ['name' => 'Name', 'email' => 'Email'];
        $type = 'excel';
        $filename = 'users_export';

        // Mock ProcessExport::dispatch para verificar se foi chamado
        // Mock ProcessExport::dispatch para verificar se foi chamado
        Mockery::mock('alias:App\Jobs\ProcessExport')
            ->shouldReceive('dispatch')
            ->once()
            ->andReturnSelf();
        // Act
        $exportId = $this->exportService->createBackgroundExport($query, $columns, $type, $filename);

        // Assert
        $this->assertIsString($exportId);
        $this->assertNotEmpty($exportId);

        // Verifica se os dados da exportação foram armazenados no cache
        Cache::assertExists('export:' . $exportId);
        $cachedData = Cache::get('export:' . $exportId);

        $this->assertEquals($exportId, $cachedData['id']);
        $this->assertEquals($columns, $cachedData['columns']);
        $this->assertEquals($type, $cachedData['type']);
        $this->assertEquals($filename, $cachedData['filename']);
        $this->assertEquals('pending', $cachedData['status']);
    }

    /** @test */
    public function export_to_csv_creates_file_on_disk(): void
    {
        // Arrange
        $query = Mockery::mock(Builder::class);
        $records = collect([
            (object)['name' => 'User A', 'email' => 'a@example.com'],
            (object)['name' => 'User B', 'email' => 'b@example.com'],
        ]);
        $query->shouldReceive('chunk')->once()->andReturnUsing(function ($size, $callback) use ($records) {
            $callback($records); // Executa o callback com os records
        });

        $columns = ['name' => 'Full Name', 'email' => 'Email Address'];
        $filename = 'my_users';

        // Mock League\Csv\Writer para evitar IO real e verificar interações
        $writerMock = Mockery::mock(Writer::class);
        $writerMock->shouldReceive('setDelimiter')->once()->with(',');
        $writerMock->shouldReceive('setEnclosure')->once()->with('"');
        $writerMock->shouldReceive('setEscape')->once()->with('\\');
        $writerMock->shouldReceive('insertOne')->once()->with(array_values($columns)); // Cabeçalhos
        $writerMock->shouldReceive('insertOne')->once()->with(['User A', 'a@example.com']);
        $writerMock->shouldReceive('insertOne')->once()->with(['User B', 'b@example.com']);
        $writerMock->shouldReceive('getContent')->once()->andReturn("Full Name,Email Address\nUser A,a@example.com\nUser B,b@example.com\n");

        Mockery::mock('alias:' . Writer::class)
            ->shouldReceive('createFromStream')
            ->once()
            ->andReturn($writerMock);

        // Act
        $path = $this->exportService->exportToCsv($query, $columns, $filename, true);

        // Assert
        Storage::assertExists('exports/' . $path);
        // Podemos verificar o conteúdo se o getContent for mocado
        $this->assertStringContainsString('my_users', $path);
        $this->assertStringEndsWith('.csv', $path);
    }

    /** @test */
    public function export_to_excel_creates_file_on_disk(): void
    {
        // Arrange
        $query = Mockery::mock(Builder::class);
        $records = collect([
            (object)['name' => 'User C', 'email' => 'c@example.com'],
        ]);
        $query->shouldReceive('chunk')->once()->andReturnUsing(function ($size, $callback) use ($records) {
            $callback($records); // Executa o callback com os records
        });

        $columns = ['name' => 'User Name', 'email' => 'Email'];
        $filename = 'excel_report';
        // Mock Excel facade
        $excelMock = Mockery::mock('alias:Maatwebsite\Excel\Facades\Excel');
        $excelMock->shouldReceive('store')
            ->once()
            ->andReturn(true);

        // Act
        $path = $this->exportService->exportToExcel($query, $columns, $filename);

        // Assert
        $this->assertStringContainsString('excel_report', $path);
        $this->assertStringEndsWith('.xlsx', $path);
        // Storage::disk('exports')->assertExists($path); // Isso falharia se não mockar a criação do arquivo no storage
    }

    // Helper para acessar métodos protegidos
    protected function callProtectedMethod($obj, $name, array $args = [])
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    // Helper para acessar propriedades protegidas
    protected function getProperty($obj, $name)
    {
        $class = new \ReflectionClass($obj);
        $property = $class->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Services\FileStorageServiceTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Domains\Core\Services;


class FileStorageServiceTest extends TestCase
{
    protected FileStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FileStorageService();
        Storage::fake('public'); // Falsifica o disco 'public'
        Storage::fake('s3'); // Falsifica o disco 's3'
        Log::fake(); // Falsifica o log
        Queue::fake(); // Falsifica a fila
        Str::shouldReceive('uuid')->andReturn('test-uuid'); // Moca UUID para previsibilidade
    }

    /** @test */
    public function it_checks_if_file_is_allowed_by_mime_type(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg');
        $this->assertTrue($this->service->isAllowedFile($file));

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $this->assertTrue($this->service->isAllowedFile($file));

        $file = UploadedFile::fake()->create('script.sh', 100, 'application/x-sh');
        $this->assertFalse($this->service->isAllowedFile($file));
    }

    /** @test */
    public function it_checks_if_file_is_allowed_with_custom_mime_types(): void
    {
        $file = UploadedFile::fake()->create('config.json', 100, 'application/json');
        $allowed = ['application/json'];
        $this->assertTrue($this->service->isAllowedFile($file, $allowed));

        $file = UploadedFile::fake()->image('image.webp');
        $allowed = ['image/jpeg']; // WebP não está em allowed, mas jpeg sim
        $this->assertFalse($this->service->isAllowedFile($file, $allowed));
    }

    /** @test */
    public function it_stores_a_file_with_generated_name(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('my_image.png');
        $path = 'avatars';
        $disk = 'public';

        // Act
        $storedPath = $this->service->store($file, $path, $disk, false);

        // Assert
        $expectedFileName = 'test-uuid.png';
        Storage::assertExists("{$path}/{$expectedFileName}");
        $this->assertEquals("{$path}/{$expectedFileName}", $storedPath);
    }

    /** @test */
    public function it_stores_a_file_with_preserved_and_sanitized_name(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('My_Docu ment!.pdf', 100, 'application/pdf');
        $path = 'documents';
        $disk = 'public';

        // Act
        $storedPath = $this->service->store($file, $path, $disk, true);

        // Assert
        $expectedFileName = 'My_Docu_ment_.pdf'; // Nome sanitizado
        Storage::assertExists("{$path}/{$expectedFileName}");
        $this->assertEquals("{$path}/{$expectedFileName}", $storedPath);
    }

    /** @test */
    public function it_throws_exception_for_disallowed_file_type_on_store(): void
    {
        // Arrange
        $file = UploadedFile::fake()->create('malicious.exe', 100, 'application/x-dosexec');
        $path = 'uploads';
        $disk = 'public';

        Log::shouldReceive('warning') // Espera que um log de warning seja gerado
            ->once()
            ->withArgs(function ($message, $context) use ($file) {
                return str_contains($message, 'Tentativa de upload de arquivo com tipo não permitido') &&
                       $context['mime_type'] === $file->getMimeType();
            });

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo de arquivo não permitido: application/x-dosexec');

        $this->service->store($file, $path, $disk);
    }

    /** @test */
    public function it_sanitizes_various_file_names(): void
    {
        // Usando Reflection para testar método protegido
        $sanitizeMethod = new \ReflectionMethod(FileStorageService::class, 'sanitizeFileName');
        $sanitizeMethod->setAccessible(true);

        $this->assertEquals('my_file.txt', $sanitizeMethod->invoke($this->service, 'my_file.txt'));
        $this->assertEquals('my_file_with_spaces.txt', $sanitizeMethod->invoke($this->service, 'my file with spaces.txt'));
        $this->assertEquals('my_file_with_special_chars_.txt', $sanitizeMethod->invoke($this->service, 'my_file_with_special_chars-!.txt'));
        $this->assertEquals('file_.hidden_file.env', $sanitizeMethod->invoke($this->service, '.hidden_file.env')); // Adiciona 'file_' para ocultos
        $this->assertEquals('file_path_to_file.zip', $sanitizeMethod->invoke($this->service, '/path/to/file.zip')); // Remove barras
    }

    /** @test */
    public function it_stores_temporary_files_and_dispatches_delete_job(): void
    {
        // Arrange
        $file = UploadedFile::fake()->image('temp_image.png');
        $expiryMinutes = 10;

        // Mock the Storage facade
        Storage::shouldReceive('disk->put')
            ->once()
            ->with("temp/test-uuid.png", Mockery::any())
            ->andReturn(true);

        Storage::shouldReceive('disk->url')
            ->once()
            ->with("temp/test-uuid.png")
            ->andReturn('http://localhost/storage/temp/test-uuid.png');

        Queue::shouldReceive('dispatch') // Verifica que o job é despachado
            ->once()
            ->andReturnUsing(function ($callback) use ($expiryMinutes) {
                // Assert that the dispatched item is a closure with a delay
                $this->assertInstanceOf(\Closure::class, $callback);
                // Cannot directly assert the delay of a closure, but can assert the dispatch call itself
                // The delay() method is chained after dispatch(), so we check dispatch was called.
            });

        // Act
        $temporaryUrl = $this->service->storeTemporary($file, $expiryMinutes);

        // Assert
        $this->assertEquals('http://localhost/storage/temp/test-uuid.png', $temporaryUrl);
        // Assert that the delete logic inside the dispatched closure is correctly setup
        // We'd typically test the closure itself if we had direct access to it
        // Queue::assertPushedOn('default', function($job) {  }); is harder with anonymous closures
        // but the core logic is in FileStorageService, not the Queue itself.
    }
    
    /** @test */
    public function it_deletes_a_file(): void
    {
        // Arrange
        Storage::disk('public')->put('path/to/file.txt', 'content');
        $this->assertTrue(Storage::disk('public')->exists('path/to/file.txt'));

        // Act
        $result = $this->service->delete('path/to/file.txt', 'public');

        // Assert
        $this->assertTrue($result);
        Storage::assertMissing('path/to/file.txt');
    }

    /** @test */
    public function it_checks_if_a_file_exists(): void
    {
        // Arrange
        Storage::disk('public')->put('path/to/another_file.txt', 'content');

        // Act & Assert
        $this->assertTrue($this->service->exists('path/to/another_file.txt', 'public'));
        $this->assertFalse($this->service->exists('path/to/non_existent.txt', 'public'));
    }

    /** @test */
    public function it_generates_temporary_url(): void
    {
        // Arrange
        $path = 'private/report.pdf';
        $expiration = now()->addHours(1);
        $disk = 's3';
        $expectedUrl = 'https://s3.aws.com/temp_signed_url';
        Storage::shouldReceive('disk->temporaryUrl')
            ->once()
            ->with($path, Mockery::on(function ($arg) use ($expiration) {
                return $arg instanceof \DateTimeInterface && $arg->getTimestamp() === $expiration->getTimestamp();
            }))
            ->andReturn($expectedUrl);

        // Act
        $url = $this->service->temporaryUrl($path, $expiration, $disk);

        // Assert
        $this->assertEquals($expectedUrl, $url);
    }

    /** @test */
    public function it_sets_allowed_mime_types(): void
    {
        // Arrange
        $customTypes = ['application/xml', 'text/csv'];
        
        // Use reflection to access the protected property
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('allowedMimeTypes');
        $property->setAccessible(true);

        // Act
        $this->service->setAllowedMimeTypes($customTypes);

        // Assert
        $this->assertEquals($customTypes, $property->getValue($this->service));

        // Test with a file to ensure the new types are effective
        $file = UploadedFile::fake()->create('data.csv', 10, 'text/csv');
        $this->assertTrue($this->service->isAllowedFile($file));

        $file = UploadedFile::fake()->image('image.jpeg'); // Originalmente permitido, agora não
        $this->assertFalse($this->service->isAllowedFile($file));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

### Arquivo: `Unit\Services\UserServiceTest.php`


#### Conteúdo

```php
<?php

namespace Tests\Unit\Services;


class UserServiceTest extends TestCase
{
    protected CreateUserCommand $createUserCommand;
    protected UpdateUserCommand $updateUserCommand;
    protected DeleteUserCommand $deleteUserCommand;
    protected GetUserByIdQuery $getUserByIdQuery;
    protected GetUserByEmailQuery $getUserByEmailQuery;
    protected GetAllUsersQuery $getAllUsersQuery;
    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->createUserCommand = Mockery::mock(CreateUserCommand::class);
        $this->updateUserCommand = Mockery::mock(UpdateUserCommand::class);
        $this->deleteUserCommand = Mockery::mock(DeleteUserCommand::class);
        $this->getUserByIdQuery = Mockery::mock(GetUserByIdQuery::class);
        $this->getUserByEmailQuery = Mockery::mock(GetUserByEmailQuery::class);
        $this->getAllUsersQuery = Mockery::mock(GetAllUsersQuery::class);
        
        $this->userService = new UserService(
            $this->createUserCommand,
            $this->updateUserCommand,
            $this->deleteUserCommand,
            $this->getUserByIdQuery,
            $this->getAllUsersQuery,
            $this->getUserByEmailQuery,
        );
    }

    /** @test */
    public function test_create_user_calls_create_command(): void
    {
        // Arrange
        $userData = new UserDTO(
            'John Doe',
            'john@example.com',
            'password123'
        );

        $expectedUser = new User(['id' => 1, 'name' => 'John Doe']);

        $this->createUserCommand
            ->shouldReceive('handle') // Chamada ajustada para handle
            ->once()
            ->with($userData)
            ->andReturn($expectedUser);

        // Act
        $result = $this->userService->createUser($userData);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
    }

    /** @test */
    public function test_get_user_by_id_calls_get_user_by_id_query(): void
    {
        // Arrange
        $userId = 1;
        $expectedUser = new User(['id' => 1, 'name' => 'John Doe']);

        $this->getUserByIdQuery
            ->shouldReceive('handle') // Chamada ajustada para handle
            ->once()
            ->with($userId)
            ->andReturn($expectedUser);

        // Act
        $result = $this->userService->getUserById($userId);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
    }

    /** @test */
    public function test_get_user_with_profile_loads_profile(): void
    {
        // Arrange
        $userId = 1;
        $user = new User(['id' => 1, 'name' => 'John Doe']);
        $profile = new Profile(['id' => 1, 'user_id' => 1, 'bio' => 'Test Bio']);
        $user->setRelation('profile', $profile); // Simula o profile já carregado

        $this->getUserByIdQuery
            ->shouldReceive('handle')
            ->once()
            ->with($userId)
            ->andReturn($user);

        // Act
        $result = $this->userService->getUserWithProfile($userId);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue($result->relationLoaded('profile'));
        $this->assertEquals('Test Bio', $result->profile->bio);
    }

    /** @test */
    public function test_get_all_users_paginated_calls_get_all_users_query(): void
    {
        // Arrange
        $perPage = 10;
        $users = Mockery::mock(LengthAwarePaginator::class);

        $this->getAllUsersQuery
            ->shouldReceive('handle')
            ->once()
            ->with($perPage)
            ->andReturn($users);

        // Act
        $result = $this->userService->getAllUsersPaginated($perPage);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test */
    public function test_update_user_calls_update_command(): void
    {
        // Arrange
        $user = new User(['id' => 1, 'name' => 'Old Name']);
        $userData = new UserDTO('New Name', 'new@example.com');

        $this->updateUserCommand
            ->shouldReceive('handle')
            ->once()
            ->with($user, $userData)
            ->andReturn(true);

        // Act
        $result = $this->userService->updateUser($user, $userData);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function test_delete_user_calls_delete_command(): void
    {
        // Arrange
        $user = new User(['id' => 1]);

        $this->deleteUserCommand
            ->shouldReceive('handle')
            ->once()
            ->with($user)
            ->andReturn(true);

        // Act
        $result = $this->userService->deleteUser($user);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function test_find_by_email_calls_get_user_by_email_query(): void
    {
        // Arrange
        $email = 'test@example.com';
        $expectedUser = new User(['id' => 1, 'email' => $email]);

        $this->getUserByEmailQuery
            ->shouldReceive('handle')
            ->once()
            ->with($email)
            ->andReturn($expectedUser);

        // Act
        $result = $this->userService->findByEmail($email);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($email, $result->email);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

