<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T-18: an INSERT that breaks ck_laptop_total_positive or ck_user_role fails in PostgreSQL itself. */
final class DatabaseChecksTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_laptop_with_zero_total_units_is_rejected_by_the_engine(): void
    {
        $this->expectExceptionMessage('ck_laptop_total_positive');

        DB::table('laptops.laptop')->insert([
            'id' => '9a1e6c64-7c6f-4d0f-9a43-2f1a0f6d1e01', 'name' => 'Latitude 3440', 'brand' => 'Dell',
            'model_code' => 'LAT-3440', 'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_units' => 0, 'available_units' => 0,
        ]);
    }

    public function test_a_user_with_an_unknown_role_is_rejected_by_the_engine(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('ck_user_role');

        DB::table('laptops.user')->insert([
            'id' => '9a1e6c64-7c6f-4d0f-9a43-2f1a0f6d1e02', 'username' => 'root', 'password_hash' => 'x', 'role' => 'superuser',
        ]);
    }
}
