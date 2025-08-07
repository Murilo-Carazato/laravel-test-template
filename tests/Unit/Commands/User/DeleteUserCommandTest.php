<?php

namespace Tests\Unit\Commands\User;

use Tests\TestCase;
use App\Domains\User\Commands\DeleteUserCommand;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Mockery;

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