<?php

namespace Croox\StatamicMeilisearchExtendable\Tests;

use Statamic\Testing\AddonTestCase;
use Croox\StatamicMeilisearchExtendable\ServiceProvider;

class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected $shouldFakeVersion = true;

    public static function provideTrueFalse(): \Generator
    {
        yield [true];
        yield [false];
    }
}
