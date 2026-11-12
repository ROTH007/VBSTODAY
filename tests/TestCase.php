<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $dbName = DB::connection()->getDatabaseName();

        if (! str_starts_with($dbName, 'vbs_test')) {
            throw new \RuntimeException(
                "Refusing to run tests: active database is \"{$dbName}\", not a vbs_test* database."
            );
        }
    }
}
