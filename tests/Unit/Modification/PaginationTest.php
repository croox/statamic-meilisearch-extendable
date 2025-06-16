<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Modification;

use Croox\StatamicMeilisearchExtendable\ConfigurationException;
use Croox\StatamicMeilisearchExtendable\Modification\Pagination;
use Meilisearch\Search\SearchResult;
use PHPUnit\Framework\Attributes\DataProvider;

class PaginationTest extends OptionModifierTestCase
{
    #[DataProvider('provideFilteringTypes')]
    public function testMeilisearchPaginationRequiresMeilisearchFiltering(string $filteringType): void
    {
        if ($filteringType === 'meilisearch') {
            $this->expectNotToPerformAssertions();
        } else {
            $this->expectException(ConfigurationException::class);
        }

        $this->createIndex(new Pagination(), [
            'meilisearch' => [
                'pagination' => [
                    'type' => 'meilisearch',
                ],
                'filtering' => [
                    'type' => $filteringType,
                ]
            ]
        ]);
    }

    #[DataProvider('provideFilteringTypes')]
    public function testStatamicPaginationCanBeUsedWithAllFilteringTypes(string $filteringType): void
    {
        $this->expectNotToPerformAssertions();

        $this->createIndex(new Pagination(), [
            'meilisearch' => [
                'pagination' => [
                    'type' => 'statamic',
                ],
                'filtering' => [
                    'type' => $filteringType,
                ]
            ]
        ]);
    }

    public function testStatamicBasedPaginationUsesHighMeilisearchPageSize(): void
    {
        $index = $this->createIndex(new Pagination(), [
            'meilisearch' => [
                'pagination' => [
                    'type' => 'statamic',
                ],
            ]
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with(
                'test',
                [ 'hitsPerPage' => 1000 ],
            )->willReturn($this->createMock(SearchResult::class));

        $index->performSearch($index->search('test'), [ ]);
    }

    public function testStatamicBasedPaginationAllowsCustomizingMeilisearchPageSize(): void
    {
        $index = $this->createIndex(new Pagination(), [
            'meilisearch' => [
                'pagination' => [
                    'type' => 'statamic',
                    'statamic_hits' => 9999,
                ],
            ]
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with(
                'test',
                [ 'hitsPerPage' => 9999 ],
            )->willReturn($this->createMock(SearchResult::class));

        $index->performSearch($index->search('test'), [ ]);
    }

    public function testMeilisearchBasedPaginationAddsOffsetAndLimitToQuery(): void
    {
        $index = $this->createIndex(new Pagination(), [
            'meilisearch' => [
                'pagination' => [
                    'type' => 'meilisearch',
                ],
                'filtering' => [
                    'type' => 'meilisearch',
                ]
            ]
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with(
                'test',
                [
                    'offset' => 100,
                    'limit' => 50,
                ],
            )->willReturn($this->createMock(SearchResult::class));

        $query = $index->search('test')->offset(100)->limit(50);
        $index->performSearch($query, [ ]);

        // The query should no longer have offset and limit set after performing the search
        // This prevents statamic from adding its own pagination logic on top of the already
        // paginated results.
        $this->assertEquals(0, $query->getOffset());
        $this->assertNull($query->getLimit());
    }

    public static function provideFilteringTypes(): \Generator
    {
        yield ['meilisearch'];
        yield ['statamic'];
        yield ['split'];
    }
}
