<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Domains\User\Services\UserService;
use App\Domains\User\Commands\CreateUserCommand;
use App\Domains\User\Commands\UpdateUserCommand;
use App\Domains\User\Commands\DeleteUserCommand;
use App\Domains\User\Queries\GetUserByIdQuery;
use App\Domains\User\Queries\GetAllUsersQuery;
use App\Domains\User\Queries\GetUserByEmailQuery;
use App\DTO\UserDTO;
use App\Models\User;
use App\Models\Profile; // Importar Profile
use Mockery;
use Illuminate\Pagination\LengthAwarePaginator; // Importar LengthAwarePaginator

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