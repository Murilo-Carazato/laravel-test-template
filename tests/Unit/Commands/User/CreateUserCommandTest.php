<?php

namespace Tests\Unit\Commands\User;

use Tests\TestCase;
use App\Domains\User\Commands\CreateUserCommand;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\DTO\UserDTO;
use App\Models\User;
use Mockery;

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