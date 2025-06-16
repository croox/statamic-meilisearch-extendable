<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Modification;

use Croox\StatamicMeilisearchExtendable\ConfigurationException;
use Croox\StatamicMeilisearchExtendable\Modification\IndexNamePrefix;
use PHPUnit\Framework\Attributes\DataProvider;

class IndexNamePrefixTest extends OptionModifierTestCase
{
    public function testPrefixesIndexName(): void
    {
        $index = $this->createIndex(new IndexNamePrefix('My Project', 'staging'), [ ]);

        $this->assertEquals(
            'my-project__staging__test-index',
            $index->name()
        );
    }

    public function testAllowsSettingOfExplicitIndexName(): void
    {
        $index = $this->createIndex(new IndexNamePrefix('My Project', 'staging'), [
            'meilisearch' => [
                'index_name' => 'custom-index-name',
            ]
        ]);

        $this->assertEquals(
            'custom-index-name',
            $index->name()
        );
    }

    public function testExplicitIndexNameMustBeOfCorrectFormat(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->createIndex(new IndexNamePrefix('My Project', 'staging'), [
            'meilisearch' => [
                'index_name' => 'SomeIndexName',
            ]
        ]);
    }

    #[DataProvider('provideDefaultAppNames')]
    public function testThrowsErrorForDefaultAppNames(string $appName, bool $shouldThrow): void
    {
        if ($shouldThrow) {
            $this->expectException(ConfigurationException::class);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $this->createIndex(new IndexNamePrefix($appName, 'staging'), [ ]);
    }

    public static function provideDefaultAppNames(): \Generator
    {
        yield [ 'Statamic', true ];
        yield [ 'Statamic Peak', true ];
        yield [ 'Laravel', true ];
        yield [ 'My Project', false ];
    }
}
