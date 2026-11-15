<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_has_exactly_the_six_documented_tables(): void
    {
        $tables = DB::table('information_schema.tables')
            ->where('table_schema', 'laptops')
            ->orderBy('table_name')
            ->pluck('table_name')
            ->all();

        $this->assertSame(['category', 'laptop', 'loan', 'loan_item', 'student', 'user'], $tables);
    }

    public function test_no_column_has_a_default_and_there_are_no_audit_columns(): void
    {
        $columns = DB::table('information_schema.columns')->where('table_schema', 'laptops')->get();

        $this->assertCount(0, $columns->whereNotNull('column_default'));
        $this->assertCount(0, $columns->whereIn('column_name', ['created_at', 'updated_at', 'status', 'days_late']));
    }

    public function test_the_five_categories_are_seeded_with_fixed_ids(): void
    {
        $categories = DB::table('laptops.category')->orderBy('id')->pluck('name', 'id')->all();

        $this->assertSame([
            '11111111-1111-4111-8111-111111111111' => 'Básicos',
            '22222222-2222-4222-8222-222222222222' => 'Diseño',
            '33333333-3333-4333-8333-333333333333' => 'Programación',
            '44444444-4444-4444-8444-444444444444' => 'Tabletas',
            '55555555-5555-4555-8555-555555555555' => 'Accesorios',
        ], $categories);
    }

    public function test_engine_rules_marked_motor_exist(): void
    {
        $constraints = collect(DB::select(
            "SELECT conname FROM pg_constraint c JOIN pg_namespace n ON n.oid = c.connamespace WHERE n.nspname = 'laptops'"
        ))->pluck('conname');
        $indexes = collect(DB::select("SELECT indexname, indexdef FROM pg_indexes WHERE schemaname = 'laptops'"));

        $this->assertContains('ck_laptop_available_range', $constraints);
        $this->assertTrue($indexes->contains(
            fn ($index) => str_contains($index->indexdef, 'UNIQUE') && str_contains($index->indexdef, '(model_code) WHERE (deleted_at IS NULL)')
        ));
        $this->assertTrue($indexes->contains(
            fn ($index) => str_contains($index->indexdef, 'UNIQUE') && str_contains($index->indexdef, '(loan_id, laptop_id)')
        ));
        $this->assertTrue($indexes->contains(
            fn ($index) => str_contains($index->indexdef, '(due_at) WHERE (returned_at IS NULL)')
        ));
    }

    public function test_available_units_can_never_exceed_total(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('laptops.laptop')->insert([
            'id' => '9a1e6c64-7c6f-4d0f-9a43-2f1a0f6d1e01',
            'name' => 'Latitude 3440',
            'brand' => 'Dell',
            'model_code' => 'LAT-3440',
            'category_id' => '11111111-1111-4111-8111-111111111111',
            'total_units' => 2,
            'available_units' => 3,
        ]);
    }
}
