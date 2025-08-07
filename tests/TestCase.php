<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure test environment
        config(['app.env' => 'testing']);
        config(['cache.default' => 'array']);
        config(['session.driver' => 'array']);
        config(['queue.default' => 'sync']);
    }
}