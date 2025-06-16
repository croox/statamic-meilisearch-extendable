<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Meilisearch;

use Croox\StatamicMeilisearchExtendable\Meilisearch\Index;
use Croox\StatamicMeilisearchExtendable\Meilisearch\Query;
use Croox\StatamicMeilisearchExtendable\Tests\TestCase;
use Meilisearch\Client;
use Meilisearch\Endpoints\Indexes;
use Meilisearch\Search\SearchResult;
use PHPUnit\Framework\MockObject\MockObject;

class QueryTest extends TestCase
{
    private MockObject&Client $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Client::class);
    }

    private function createQuery(string $search, array $modifiers = [ ]): Query
    {
        $index = new Index(
            $this->client,
            'test_index',
            [ 'meilisearch_modifiers' => [ ] ]
        );

        return $index->search($search);
    }

    public function testHasGetterForTerm(): void
    {
        $query = $this->createQuery('test search');

        $this->assertEquals('test search', $query->getQuery());
    }

    public function testPerformsSearch(): void
    {
        $query = $this->createQuery('test search');

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test search')
            ->willReturn(new SearchResult([
                'totalHits' => 12345,
                'totalPages' => 4115,
                'page' => 1,
                'hitsPerPage' => 3,
                'processingTimeMs' => 10,
                'query' => 'test search',
                'hits' => [
                    [ 'id' => '123' ],
                    [ 'id' => '456' ],
                    [ 'id' => '789' ],
                ]
            ]));

        $results = $query->getSearchResults();

        $this->assertCount(3, $results);
        $this->assertEquals('123', $results[0]['id']);
        $this->assertEquals('456', $results[1]['id']);
        $this->assertEquals('789', $results[2]['id']);
    }

    private function mockMeiliIndex(?string $name = null): MockObject&Indexes
    {
        $index = $this->createMock(Indexes::class);

        $this->client
            ->expects($this->atLeastOnce())
            ->method('index')
            ->with($name ?? 'test_index')
            ->willReturn($index);

        return $index;
    }
}
