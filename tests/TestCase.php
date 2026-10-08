<?php

namespace Tests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * TenantContext is process-static by design (see its docblock) — real
     * requests get a fresh value every time via SetTenantContext, but nothing
     * resets it between test methods in the same PHPUnit process. Without
     * this, one test's actingAs() leaks into the next test's factory calls
     * (a franchise-level user silently getting stamped with the previous
     * test's organization_id) — flush before and after every test instead.
     */
    protected function setUp(): void
    {
        parent::setUp();

        TenantContext::flush();
    }

    protected function tearDown(): void
    {
        TenantContext::flush();

        parent::tearDown();
    }
}
