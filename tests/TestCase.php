<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Symfony sends "Accept-Language: en-us" by default, the app is tested in German.
        $this->withHeader('Accept-Language', 'de-DE,de;q=0.9');
    }
}
