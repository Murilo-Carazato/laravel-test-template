<?php

namespace Tests\Unit\Commands\User;

use Tests\TestCase;
use App\Domains\User\Commands\UpdateUserCommand;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\DTO\UserDTO;
use App\Models\User;
use Mockery;

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