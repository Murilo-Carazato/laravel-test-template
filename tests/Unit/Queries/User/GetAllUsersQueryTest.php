<?php

namespace Tests\Unit\Queries\User;

use Tests\TestCase;
use App\Domains\User\Queries\GetAllUsersQuery;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Mockery;
use Illuminate\Pagination\LengthAwarePaginator;

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