<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Modification;

use Croox\StatamicMeilisearchExtendable\Modification\FacetsOptionModifier;
use Illuminate\Http\Request;
use Meilisearch\Search\SearchResult;

class FacetsOptionModifierTest extends OptionModifierTestCase
{

    public function testDefinesFilterableAttributes(): void
    {
        $request = Request::create('/search');
        $index = $this->createIndex(new FacetsOptionModifier($request), [
            'meilisearch' => [
                'facets' => [
                    'tags',
                    'date',
                    'num',
                ],
            ],
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('updateSettings')
            ->with([
                'filterableAttributes' => [ 'tags', 'date', 'num' ],
                'faceting' => [
                    'sortFacetValuesBy' => [
                        '*' => 'count',
                    ],
                ],
            ]);

        $index->update();
    }

    public function testAddsFacetsToQuery(): void
    {
        $request = Request::create('/search');
        $index = $this->createIndex(new FacetsOptionModifier($request), [
            'meilisearch' => [
                'facets' => [
                    'tags',
                    'date',
                    'num',
                ],
            ],
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [
                'facets' => [ 'tags', 'date', 'num' ],
            ])->willReturn($this->mock(SearchResult::class));

        $index->performSearch($index->search('test'), [ ]);
    }

    public function testAddsFilterForDiscreteValues(): void
    {
        $request = Request::create('/search', parameters: [
            'facet' => [
                'tags' => [ 'values' => [ 'tag1', 'tag2' ] ],
            ]
        ]);

        $index = $this->createIndex(new FacetsOptionModifier($request), [
            'meilisearch' => [
                'facets' => [
                    'tags',
                    'date',
                    'num',
                ],
            ],
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [
                'facets' => [ 'tags', 'date', 'num' ],
                'filter' => [ 'tags IN ["tag1","tag2"]' ],
            ])->willReturn($this->mock(SearchResult::class));

        $index->performSearch($index->search('test'), [ ]);
    }

    public function testAddsFilterForRangeValues(): void
    {
        $request = Request::create('/search', parameters: [
            'facet' => [
                'num' => [ 'min' => 10, 'max' => 20 ],
            ]
        ]);

        $index = $this->createIndex(new FacetsOptionModifier($request), [
            'meilisearch' => [
                'facets' => [
                    'tags',
                    'date',
                    'num',
                ],
            ],
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [
                'facets' => [ 'tags', 'date', 'num' ],
                'filter' => [ 'num >= 10.000000', 'num <= 20.000000' ],
            ])->willReturn($this->mock(SearchResult::class));

        $index->performSearch($index->search('test'), [ ]);
    }

    public function testAddsFilterForDateRangeValues(): void
    {
        $request = Request::create('/search', parameters: [
            'facet' => [
                'date' => [ 'date_min' => '2023-01-01', 'date_max' => '2023-12-31' ],
            ]
        ]);

        $index = $this->createIndex(new FacetsOptionModifier($request), [
            'meilisearch' => [
                'facets' => [
                    'tags',
                    'date',
                    'num',
                ],
            ],
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [
                'facets' => [ 'tags', 'date', 'num' ],
                'filter' => [ 'date >= 1672531200.000000', 'date <= 1703980800.000000' ],
            ])->willReturn($this->mock(SearchResult::class));

        $index->performSearch($index->search('test'), [ ]);
    }

    public function testDoesNothingIfNoFacetsConfigurred(): void
    {
        $request = Request::create('/search', parameters: [
            'facet' => [
                'tags' => [ 'values' => [ 'tag1', 'tag2' ] ],
                'num' => [ 'min' => 10, 'max' => 20 ],
                'date' => [ 'date_min' => '2023-01-01', 'date_max' => '2023-12-31' ],
            ]
        ]);

        $index = $this->createIndex(new FacetsOptionModifier($request), [ ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [])
            ->willReturn($this->mock(SearchResult::class));

        $index->performSearch($index->search('test'), [ ]);
    }
}
