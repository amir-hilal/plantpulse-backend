<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_user()
    {
        DB::beginTransaction();

        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'testuser@example.com',
            'username' => 'test_user',
            'password' => Hash::make('password'),
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_can_update_a_user()
    {
        DB::beginTransaction();

        $user = User::factory()->create(); // Use factory to create a user

        $user->first_name = 'Updated';
        $user->last_name = 'User';
        $user->username = 'updated_user';
        $user->save();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated',
            'last_name' => 'User',
            'username' => 'updated_user',
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_can_soft_delete_a_user()
    {
        DB::beginTransaction();

        $user = User::factory()->create(); // Create a new user

        // Soft delete the user
        $user->delete();

        // Assert the user is soft deleted
        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);

        DB::rollBack();
    }

    /** @test */
    public function it_can_restore_a_soft_deleted_user()
    {
        DB::beginTransaction();

        $user = User::factory()->create(); // Create a new user
        $user->delete(); // Soft delete the user

        // Restore the user
        $user->restore();

        // Assert the user is restored
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);

        DB::rollBack();
    }
}
