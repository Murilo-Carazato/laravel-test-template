<?php

namespace Tests\Unit\DTO;

use Tests\TestCase;
use App\DTO\UserDTO;

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