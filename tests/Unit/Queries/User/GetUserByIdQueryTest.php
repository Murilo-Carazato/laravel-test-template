<?php

namespace Tests\Unit\Queries\User;

use Tests\TestCase;
use App\Domains\User\Queries\GetUserByIdQuery;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Mockery;

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