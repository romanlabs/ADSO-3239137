<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    private const TEST_DATABASE = 'laptops_test';

    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests migrate, roll back and delete rows: they must never touch the database the system runs on.
        $database = DB::connection()->getDatabaseName();
        if ($database !== self::TEST_DATABASE || ! $this->app->environment('testing')) {
            throw new LogicException("Tests must run on the testing environment and the '".self::TEST_DATABASE."' database, not '{$database}'.");
        }
    }
}
