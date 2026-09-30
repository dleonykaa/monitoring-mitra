<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    /**
     * Semua tes memakai disk public palsu agar unggahan foto dan gambar contoh dari seeder
     * tidak pernah tertulis ke storage aplikasi yang sebenarnya.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }
}
