<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PackageSeeder::class);
    }

    public function test_signup_with_code_creates_account_shop_and_token(): void
    {
        $code = $this->postJson('/api/v1/auth/request-code', ['email' => 'Asha@Example.test', 'purpose' => 'signup', 'name' => 'Asha Patil', 'phone' => '9810000123'])
            ->assertOk()
            ->json('debug_code');

        $response = $this->postJson('/api/v1/auth/verify', ['email' => 'asha@example.test', 'code' => $code, 'purpose' => 'signup', 'name' => 'Asha Patil', 'phone' => '9810000123'])
            ->assertOk()
            ->assertJsonPath('account.user.first_name', 'Asha')
            ->assertJsonPath('account.shop.setup_complete', false);

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('shops', ['whatsapp' => '+91 98100 00123']);
    }

    public function test_wrong_code_counts_down_and_then_locks(): void
    {
        User::factory()->create(['email' => 'ramesh@example.test']);
        $code = $this->postJson('/api/v1/auth/request-code', ['email' => 'ramesh@example.test', 'purpose' => 'login'])->json('debug_code');
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->postJson('/api/v1/auth/verify', ['email' => 'ramesh@example.test', 'code' => $wrong, 'purpose' => 'login'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', 'That code is not right. 4 tries left.');

        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/v1/auth/verify', ['email' => 'ramesh@example.test', 'code' => $wrong, 'purpose' => 'login']);
        }

        $this->postJson('/api/v1/auth/verify', ['email' => 'ramesh@example.test', 'code' => $code, 'purpose' => 'login'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.code.0', 'Too many wrong tries. Ask for a new code.');
    }

    public function test_login_for_unknown_email_points_to_signup(): void
    {
        $this->postJson('/api/v1/auth/request-code', ['email' => 'nobody@example.test', 'purpose' => 'login'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_app_routes_need_a_token(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }
}
