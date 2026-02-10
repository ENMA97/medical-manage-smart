<?php

namespace Tests\Feature\Api\Auth;

use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function inactive_user_cannot_login()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function authenticated_user_can_get_profile()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes()
    {
        // Act
        $response = $this->getJson('/api/auth/me');

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function user_can_logout()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/logout');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function user_can_change_password()
    {
        // Arrange
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/change-password', [
                'current_password' => 'oldpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify password was changed
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /** @test */
    public function user_cannot_change_password_with_wrong_current_password()
    {
        // Arrange
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/change-password', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function login_validates_required_fields()
    {
        // Act
        $response = $this->postJson('/api/auth/login', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /** @test */
    public function login_validates_email_format()
    {
        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
