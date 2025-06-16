<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Modification;

use Croox\StatamicMeilisearchExtendable\Modification\AdditionalQueryOptions;
use Meilisearch\Search\SearchResult;

class AdditionalQueryOptionsTest extends OptionModifierTestCase
{
    public function testAddsQueryOptionsFromConfiguration(): void
    {
        $index = $this->createIndex(new AdditionalQueryOptions(), [
            'meilisearch' => [
                'query_options' => [
                    'foo' => 'bar',
                ]
            ]
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test search', [ 'foo' => 'bar', 'test' => 'test' ])
            ->willReturn($this->createMock(SearchResult::class));

        $index->performSearch($index->search('test search'), [ 'test' => 'test' ]);
    }

    public function testAddsNothingIfNoQueryOptionsConfigured(): void
    {
        $index = $this->createIndex(new AdditionalQueryOptions(), [ ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test search', [ 'test' => 'test' ])
            ->willReturn($this->createMock(SearchResult::class));

        $index->performSearch($index->search('test search'), [ 'test' => 'test' ]);
    }
}
