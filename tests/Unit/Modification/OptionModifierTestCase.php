<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Modification;

use Croox\StatamicMeilisearchExtendable\Meilisearch\Index;
use Croox\StatamicMeilisearchExtendable\Modification\MeilisearchOptionModifier;
use Croox\StatamicMeilisearchExtendable\Tests\TestCase;
use Meilisearch\Client;
use Meilisearch\Endpoints\Indexes;
use PHPUnit\Framework\MockObject\MockObject;

abstract class OptionModifierTestCase extends TestCase
{
    protected MockObject&Client $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Client::class);
    }

    protected function createIndex(MeilisearchOptionModifier $modifier, array $config): Index
    {
        return new Index(
            $this->client,
            'test_index',
            [
                'meilisearch_modifiers' => [ $modifier ],
                ...$config,
            ]
        );
    }

    protected function mockMeiliIndex(?string $name = null): MockObject&Indexes
    {
        $index = $this->createMock(Indexes::class);

        $this->client
            ->method('index')
            ->with($name ?? 'test_index')
            ->willReturn($index);

        return $index;
    }
}
