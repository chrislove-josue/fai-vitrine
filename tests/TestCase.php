<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\RefreshThreeDatabases;

abstract class TestCase extends BaseTestCase
{
    use RefreshThreeDatabases;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refreshDatabasesAndBeginTransactions();
    }

    protected function tearDown(): void
    {
        $this->rollbackAllTransactions();

        parent::tearDown();
    }
}
