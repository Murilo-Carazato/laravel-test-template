<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;
use App\Policies\UserPolicy;
use App\Models\User;

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