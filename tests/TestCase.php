<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Database\Seeders\CategorySeeder;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'permission.cache.store' => 'array',
        ]);
        app(PermissionRegistrar::class)->initializeCache();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->seed(CategorySeeder::class);
    }
}
