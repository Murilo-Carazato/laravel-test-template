<?php

namespace Tests\Unit\Queries\User;

use Tests\TestCase;
use App\Domains\User\Queries\GetUserByEmailQuery;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Mockery;

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