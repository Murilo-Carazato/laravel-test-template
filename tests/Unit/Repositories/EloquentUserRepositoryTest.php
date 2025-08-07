<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Models\User;
use App\Models\Profile; // Importar Profile
use App\DTO\UserDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException; // Importar ModelNotFoundException
use Illuminate\Support\Facades\Hash; // Importar Hash

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