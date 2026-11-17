<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T-17 and plan D-10: on an empty database the start of the system creates the admin from ADMIN_USERNAME and
 * ADMIN_PASSWORD, and that admin can log in. Starting again changes nothing.
 */
final class BootstrapAdminTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'initial-admin-password';

    public function test_an_empty_database_ends_with_one_admin_that_can_log_in(): void
    {
        $this->assertSame(0, DB::table('laptops.user')->count());
        config(['laptops.admin_username' => ' Admin ', 'laptops.admin_password' => self::PASSWORD]);

        $this->artisan('app:bootstrap-admin')->expectsOutput('Initial admin created.')->assertSuccessful();

        $this->assertSame([['admin', 'admin']], DB::table('laptops.user')->get(['username', 'role'])->map(fn ($row) => [$row->username, $row->role])->all());
        $this->assertStringStartsWith('$argon2id$', DB::table('laptops.user')->value('password_hash'));

        $this->postJson('/api/v1/auth/login', ['username' => 'admin', 'password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonPath('role', 'admin');
    }

    public function test_running_it_again_keeps_a_single_admin(): void
    {
        config(['laptops.admin_username' => 'admin', 'laptops.admin_password' => self::PASSWORD]);

        $this->artisan('app:bootstrap-admin')->assertSuccessful();
        $this->artisan('app:bootstrap-admin')->expectsOutput('Initial admin already exists; nothing to do.')->assertSuccessful();

        $this->assertSame(1, DB::table('laptops.user')->count());
    }

    public function test_missing_or_short_variables_stop_the_start_without_creating_anyone(): void
    {
        config(['laptops.admin_username' => null, 'laptops.admin_password' => 'corta']);

        $this->artisan('app:bootstrap-admin')
            ->expectsOutput('ADMIN_USERNAME: obligatoria')
            ->expectsOutput('ADMIN_PASSWORD: mínimo 8 caracteres')
            ->assertFailed();

        $this->assertSame(0, DB::table('laptops.user')->count());
    }
}
