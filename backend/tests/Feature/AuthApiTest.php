<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_returns_user_and_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Member',
            'email' => 'jane@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()->assertJsonPath('data.user.email', 'jane@example.test')->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_rejects_bad_credentials(): void
    {
        User::create(['name' => 'Jane', 'email' => 'jane@example.test', 'password' => Hash::make('password')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'jane@example.test', 'password' => 'wrongpass'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
