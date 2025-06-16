<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Modification;

use Croox\StatamicMeilisearchExtendable\Meilisearch\Query;
use Croox\StatamicMeilisearchExtendable\Modification\Filtering;
use Meilisearch\Search\SearchResult;
use PHPUnit\Framework\Attributes\DataProvider;

class FilteringTest extends OptionModifierTestCase
{

    public function testAddsFilterableAttributes(): void
    {
        $index = $this->createIndex(new Filtering(), [
            'meilisearch' => [
                'filtering' => [
                    'attributes' => [
                        'tag',
                        'date',
                    ]
                ]
            ]
         ]);

        $config = $index->config();
        $this->assertEquals(
            ['tag', 'date'],
            $config['settings']['filterableAttributes']
        );
    }

    public function testAddsWheresToMeilisearchQueryForTypeMeilisearch(): void
    {
        $index = $this->createIndex(new Filtering(), [
            'meilisearch' => [
                'filtering' => [
                    'type' => 'meilisearch',
                    'attributes' => [
                        'tag',
                    ]
                ]
            ]
        ]);

        // Filter is added to the meilisearch query
        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [
                'filter' => [
                    'tag = "test"'
                ]
            ])->willReturn($this->createMock(SearchResult::class));

        $query = $index->search('test')
            ->where('tag', 'test');

        $index->performSearch($query, [ ]);

        $this->assertEmpty($query->getWheres());

    }

    public function testAddsWheresToMeilisearchQueryForTypeSplit(): void
    {
        $index = $this->createIndex(new Filtering(), [
            'meilisearch' => [
                'filtering' => [
                    'type' => 'split',
                    'attributes' => [
                        'tag',
                    ]
                ]
            ]
        ]);

        // Filter is added to the meilisearch query
        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [
                'filter' => [
                    'tag = "test"'
                ]
            ])->willReturn($this->createMock(SearchResult::class));

        $query = $index->search('test')
            ->where('tag', 'test');

        $index->performSearch($query, [ ]);

        $this->assertEmpty($query->getWheres());

    }

    public function testDoesNothingForTypeStatamic(): void
    {
        $index = $this->createIndex(new Filtering(), [
            'meilisearch' => [
                'filtering' => [
                    'type' => 'statamic',
                    'attributes' => [
                        'tag',
                    ]
                ]
            ]
        ]);

        // Filter is not added to the meilisearch query
        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [])
            ->willReturn($this->createMock(SearchResult::class));

        $query = $index->search('test')
            ->where('tag', 'test');

        $index->performSearch($query, [ ]);

        $this->assertCount(1, $query->getWheres());
    }

    public function testDoesNothingIfNoTypeSet(): void
    {
        $index = $this->createIndex(new Filtering(), [
            'meilisearch' => [
                'filtering' => [
                    'attributes' => [
                        'tag',
                    ]
                ]
            ]
        ]);

        // Filter is not added to the meilisearch query
        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [])
            ->willReturn($this->createMock(SearchResult::class));

        $query = $index->search('test')
            ->where('tag', 'test');

        $index->performSearch($query, [ ]);

        $this->assertCount(1, $query->getWheres());
    }

    public function testTypeSplitAllowsUnhandledMeilisearchWheres(): void
    {
        $index = $this->createIndex(new Filtering(), [
            'meilisearch' => [
                'filtering' => [
                    'type' => 'split',
                    'attributes' => [
                        'tag',
                    ]
                ]
            ]
        ]);

        // Filter is added to the meilisearch query
        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [
                'filter' => [
                    'tag = "test"'
                ]
            ])->willReturn($this->createMock(SearchResult::class));

        $query = $index->search('test')
            ->where('tag', 'test')
            ->where('unhandled', 'value');

        $index->performSearch($query, [ ]);

        // One unhandled where is still present - it will be handled by statamic
        $this->assertCount(1, $query->getWheres());
    }

    public function testTypeMeilisearchDoesNotAllowUnhandledWheres(): void
    {
        $index = $this->createIndex(new Filtering(), [
            'meilisearch' => [
                'filtering' => [
                    'type' => 'meilisearch',
                    'attributes' => [
                        'tag',
                    ]
                ]
            ]
        ]);

        $query = $index->search('test')
            ->where('tag', 'test')
            ->where('unhandled', 'value');

        // The unhandled where will lead to an exception
        $this->expectException(\InvalidArgumentException::class);
        $index->performSearch($query, [ ]);
    }

    #[DataProvider('provideQueryConditions')]
    public function testTransformsQueriesToMeilisearchFormat(callable $queryModifier, array $expected): void
    {
        $index = $this->createIndex(new Filtering(), [
            'meilisearch' => [
                'filtering' => [
                    'type' => 'meilisearch',
                    'attributes' => [
                        'tag',
                        'count',
                    ]
                ]
            ]
        ]);

        $query = $index->search('test');
        $query = $queryModifier($query);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [
                'filter' => $expected
            ])
            ->willReturn($this->createMock(SearchResult::class));

        $index->performSearch($query, [ ]);
    }

    public static function provideQueryConditions(): \Generator
    {
        yield 'equal' => [
            fn (Query $query) => $query->where('tag', 'test'),
            [ 'tag = "test"' ]
        ];
        yield 'not equal' => [
            fn (Query $query) => $query->where('tag', '!=', 'test'),
            [ 'tag != "test"' ]
        ];
        yield 'greater than' => [
            fn (Query $query) => $query->where('tag', '>', 'test'),
            [ 'tag > "test"' ]
        ];
        yield 'greater than or equal' => [
            fn (Query $query) => $query->where('tag', '>=', 'test'),
            [ 'tag >= "test"' ]
        ];
        yield 'less than' => [
            fn (Query $query) => $query->where('tag', '<', 'test'),
            [ 'tag < "test"' ]
        ];
        yield 'less than or equal' => [
            fn (Query $query) => $query->where('tag', '<=', 'test'),
            [ 'tag <= "test"' ]
        ];
        yield 'between' => [
            fn (Query $query) => $query->whereBetween('count', [ 1, 10 ]),
            [ 'count >= 1 AND count <= 10' ]
        ];
        yield 'not between' => [
            fn (Query $query) => $query->whereNotBetween('count', [ 1, 10 ]),
            [ 'count < 1 OR count > 10' ]
        ];
        yield 'in' => [
            fn (Query $query) => $query->whereIn('tag', [ 'test1', 'test2' ]),
            [ 'tag IN ["test1", "test2"]' ]
        ];
        yield 'column equal' => [
            fn (Query $query) => $query->whereColumn('tag', 'count'),
            [ 'tag = count' ]
        ];
        yield 'column not equal' => [
            fn (Query $query) => $query->whereColumn('tag', '!=', 'count'),
            [ 'tag != count' ]
        ];
        yield 'column greater than' => [
            fn (Query $query) => $query->whereColumn('tag', '>', 'count'),
            [ 'tag > count' ]
        ];
        yield 'column greater than or equal' => [
            fn (Query $query) => $query->whereColumn('tag', '>=', 'count'),
            [ 'tag >= count' ]
        ];
        yield 'column less than' => [
            fn (Query $query) => $query->whereColumn('tag', '<', 'count'),
            [ 'tag < count' ]
        ];
        yield 'column less than or equal' => [
            fn (Query $query) => $query->whereColumn('tag', '<=', 'count'),
            [ 'tag <= count' ]
        ];
    }
}
