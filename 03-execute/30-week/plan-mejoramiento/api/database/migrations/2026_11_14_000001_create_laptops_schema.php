<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CATEGORIES = [
        ['11111111-1111-4111-8111-111111111111', 'Básicos'],
        ['22222222-2222-4222-8222-222222222222', 'Diseño'],
        ['33333333-3333-4333-8333-333333333333', 'Programación'],
        ['44444444-4444-4444-8444-444444444444', 'Tabletas'],
        ['55555555-5555-4555-8555-555555555555', 'Accesorios'],
    ];

    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS laptops');

        Schema::create('laptops.category', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120)->unique();
        });

        Schema::create('laptops.laptop', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 200);
            $table->string('brand', 120);
            $table->string('model_code', 20);
            $table->uuid('category_id');
            $table->integer('total_units');
            $table->integer('available_units');
            $table->string('photo_key', 512)->nullable();
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('category_id')->references('id')->on('laptops.category')->restrictOnDelete();
        });

        Schema::create('laptops.student', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name', 200);
            $table->string('document_number', 30)->unique();
        });

        Schema::create('laptops.user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username', 120)->unique();
            $table->string('password_hash', 512);
            $table->string('role', 40);
        });

        Schema::create('laptops.loan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('loaned_by_user_id');
            $table->timestampTz('loaned_at');

            $table->foreign('student_id')->references('id')->on('laptops.student')->restrictOnDelete();
            $table->foreign('loaned_by_user_id')->references('id')->on('laptops.user')->restrictOnDelete();
            $table->index('loaned_at');
            $table->index('student_id');
        });

        Schema::create('laptops.loan_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->uuid('laptop_id');
            $table->string('laptop_name', 200);
            $table->string('model_code', 20);
            $table->string('category_name', 120);
            $table->timestampTz('due_at');
            $table->timestampTz('returned_at')->nullable();

            $table->foreign('loan_id')->references('id')->on('laptops.loan')->cascadeOnDelete();
            $table->foreign('laptop_id')->references('id')->on('laptops.laptop')->restrictOnDelete();
            $table->unique(['loan_id', 'laptop_id']);
            $table->index('laptop_id');
        });

        DB::statement('ALTER TABLE laptops.laptop ADD CONSTRAINT ck_laptop_available_range '
            .'CHECK (available_units >= 0 AND available_units <= total_units)');
        DB::statement('CREATE UNIQUE INDEX ux_laptop_model_code_active ON laptops.laptop (model_code) '
            .'WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX ix_laptop_category_name_active ON laptops.laptop (category_id, name) '
            .'WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX ix_loan_item_due_open ON laptops.loan_item (due_at) '
            .'WHERE returned_at IS NULL');

        foreach (self::CATEGORIES as [$id, $name]) {
            DB::table('laptops.category')->insert(['id' => $id, 'name' => $name]);
        }
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS laptops CASCADE');
    }
};
