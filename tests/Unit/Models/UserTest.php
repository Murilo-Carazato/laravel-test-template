<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\FeatureUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Collection; // Importar Collection
use Carbon\Carbon; // Importar Carbon

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'device_token' => 'some_device_token_xyz',
        ]);
    }

    /** @test */
    public function it_has_fillable_attributes(): void
    {
        $fillable = [
            'name',
            'email',
            'password',
            'device_token',
        ];

        $this->assertEquals($fillable, $this->user->getFillable());
    }

    /** @test */
    public function it_has_hidden_attributes(): void
    {
        $hidden = [
            'password',
            'remember_token',
        ];

        $this->assertEquals($hidden, $this->user->getHidden());
    }

    /** @test */
    public function it_has_casts(): void
    {
        $casts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];

        // Comparar apenas as chaves esperadas, pois Laravel adiciona 'id' => 'int' automaticamente
        foreach ($casts as $key => $value) {
            $this->assertArrayHasKey($key, $this->user->getCasts());
            $this->assertEquals($value, $this->user->getCasts()[$key]);
        }
    }

    /** @test */
    public function it_can_create_user_with_valid_data(): void
    {
        $userData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Jane Smith', $user->name);
        $this->assertEquals('jane@example.com', $user->email);
        $this->assertNotNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('users', [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);
    }

    /** @test */
    public function it_has_profile_relationship(): void
    {
        $profile = Profile::factory()->create(['user_id' => $this->user->id]);

        $this->assertInstanceOf(Profile::class, $this->user->profile);
        $this->assertEquals($profile->id, $this->user->profile->id);
    }

    /** @test */
    public function it_has_features_relationship(): void // Renomeado para 'features'
    {
        $featureUser = FeatureUser::factory()->create(['user_id' => $this->user->id]);

        $this->assertInstanceOf(Collection::class, $this->user->features); // Acessando 'features'
        $this->assertCount(1, $this->user->features);
        $this->assertEquals($featureUser->feature_name, $this->user->features->first()->name);
    }

    /** @test */
    public function it_automatically_hashes_password(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'plaintext-password',
        ]);

        $this->assertTrue(Hash::check('plaintext-password', $user->password));
        $this->assertNotEquals('plaintext-password', $user->password);
    }

    /** @test */
    public function it_handles_email_verification_date_properly(): void
    {
        $verifiedUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->assertNotNull($verifiedUser->email_verified_at);
        $this->assertNull($unverifiedUser->email_verified_at);
    }

    /** @test */
    public function it_has_proper_table_name(): void
    {
        $this->assertEquals('users', $this->user->getTable());
    }

    /** @test */
    public function it_has_proper_primary_key(): void
    {
        $this->assertEquals('id', $this->user->getKeyName());
    }

    /** @test */
    public function it_uses_timestamps(): void
    {
        $this->assertTrue($this->user->usesTimestamps());
        $this->assertNotNull($this->user->created_at);
        $this->assertNotNull($this->user->updated_at);
    }

    /** @test */
    public function it_can_delete_user(): void
    {
        $userId = $this->user->id;
        $this->user->delete();
        
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    /** @test */
    public function it_can_update_user_attributes(): void
    {
        $this->user->update([
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $this->assertEquals('Updated Name', $this->user->fresh()->name);
        $this->assertEquals('updated@example.com', $this->user->fresh()->email);
    }

    /** @test */
    public function it_can_find_user_by_email(): void
    {
        $foundUser = User::where('email', $this->user->email)->first();

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($this->user->id, $foundUser->id);
        $this->assertEquals($this->user->email, $foundUser->email);
    }

    /** @test */
    public function it_returns_correct_attributes_array(): void
    {
        $attributes = $this->user->getAttributes();

        $this->assertIsArray($attributes);
        $this->assertArrayHasKey('id', $attributes);
        $this->assertArrayHasKey('name', $attributes);
        $this->assertArrayHasKey('email', $attributes);
        $this->assertArrayHasKey('created_at', $attributes);
        $this->assertArrayHasKey('updated_at', $attributes);
        $this->assertArrayHasKey('device_token', $attributes);
    }

    /** @test */
    public function it_can_convert_to_array(): void
    {
        $userArray = $this->user->toArray();

        $this->assertIsArray($userArray);
        $this->assertEquals($this->user->id, $userArray['id']);
        $this->assertEquals($this->user->name, $userArray['name']);
        $this->assertEquals($this->user->email, $userArray['email']);
        $this->assertArrayNotHasKey('password', $userArray); // Hidden attribute
    }

    /** @test */
    public function it_can_convert_to_json(): void
    {
        $userJson = $this->user->toJson();
        $decoded = json_decode($userJson, true);

        $this->assertIsString($userJson);
        $this->assertEquals($this->user->id, $decoded['id']);
        $this->assertEquals($this->user->name, $decoded['name']);
        $this->assertEquals($this->user->email, $decoded['email']);
        $this->assertArrayNotHasKey('password', $decoded); // Hidden attribute
    }

    /** @test */
    public function it_handles_email_verified_at_casting(): void
    {
        $verificationDate = Carbon::now();
        
        $this->user->update(['email_verified_at' => $verificationDate]);
        
        $this->assertInstanceOf(Carbon::class, $this->user->fresh()->email_verified_at);
        $this->assertEquals($verificationDate->toDateTimeString(), 
                          $this->user->fresh()->email_verified_at->toDateTimeString());
    }

    /** @test */
    public function it_can_store_and_retrieve_device_token(): void
    {
        $token = 'new_device_token_abc';
        $this->user->update(['device_token' => $token]);

        $this->assertEquals($token, $this->user->fresh()->device_token);
    }
}