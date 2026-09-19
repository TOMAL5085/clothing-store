<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase2UsersAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_hashes_password_and_issues_email_otp(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada Customer',
            'email' => 'ada@example.test',
            'phone' => '+15555550123',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'ada@example.test')
            ->assertJsonPath('data.user.phone', '+15555550123')
            ->assertJsonPath('data.user.emailVerified', false)
            ->assertJsonStructure(['data' => ['token', 'debugOtp']]);

        $user = User::where('email', 'ada@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseHas('user_otps', ['user_id' => $user->id, 'purpose' => 'email_verification']);
    }

    public function test_duplicate_registration_and_inactive_login_are_rejected(): void
    {
        User::factory()->create(['email' => 'jane@example.test', 'status' => 'inactive']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane',
            'email' => 'jane@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.test',
            'password' => 'password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_otp_verification_accepts_valid_code_and_rejects_reuse_or_expired_codes(): void
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sam Verified',
            'email' => 'sam@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated();

        $token = $register->json('data.token');
        $code = $register->json('data.debugOtp');

        $this->withToken($token)
            ->postJson('/api/v1/auth/otp/verify', ['code' => '111111'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->withToken($token)
            ->postJson('/api/v1/auth/otp/verify', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('data.emailVerified', true);

        $this->withToken($token)
            ->postJson('/api/v1/auth/otp/verify', ['code' => $code])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        UserOtp::query()->latest()->firstOrFail()->update(['last_sent_at' => now()->subMinutes(2)]);
        $newOtp = $this->withToken($token)->postJson('/api/v1/auth/otp')->assertOk()->json('debugOtp');
        UserOtp::query()->whereNull('verified_at')->latest('id')->firstOrFail()->update(['expires_at' => now()->subMinute()]);

        $this->withToken($token)
            ->postJson('/api/v1/auth/otp/verify', ['code' => $newOtp])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_profile_and_password_updates_are_protected_and_do_not_allow_privileged_fields(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->putJson('/api/v1/profile', ['name' => 'Nope', 'email' => 'nope@example.test'])->assertUnauthorized();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'name' => 'Updated Customer',
                'email' => 'updated@example.test',
                'phone' => '+15550001111',
                'role' => 'admin',
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Customer')
            ->assertJsonPath('data.phone', '+15550001111')
            ->assertJsonPath('data.role', 'customer')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.emailVerified', false);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_customer_can_manage_only_their_own_addresses(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $otherAddress = Address::create([
            'user_id' => $other->id,
            'first_name' => 'Other',
            'last_name' => 'Customer',
            'email' => 'other@example.test',
            'address' => '9 Other Street',
            'city' => 'Other City',
            'postal_code' => '10001',
            'country' => 'US',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/addresses', [
                'label' => 'Home',
                'firstName' => 'Ada',
                'lastName' => 'Customer',
                'email' => 'ada@example.test',
                'phone' => '+15550002222',
                'address' => '1 Main Street',
                'city' => 'New York',
                'postalCode' => '10001',
                'country' => 'US',
                'isDefault' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.label', 'Home')
            ->assertJsonPath('data.isDefault', true);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/addresses/{$otherAddress->id}")
            ->assertForbidden();

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/addresses')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_manage_customer_status_and_customer_cannot_access_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'status' => 'active']);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/admin/customers')
            ->assertForbidden();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/customers?q='.$customer->email)
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.email', $customer->email);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/admin/customers/{$customer->id}/status", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');
    }
}
