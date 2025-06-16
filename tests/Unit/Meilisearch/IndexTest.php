<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Meilisearch;

use Croox\StatamicMeilisearchExtendable\ConfigurationException;
use Croox\StatamicMeilisearchExtendable\Meilisearch\Index;
use Croox\StatamicMeilisearchExtendable\Meilisearch\Query;
use Croox\StatamicMeilisearchExtendable\Modification\AdditionalQueryOptions;
use Croox\StatamicMeilisearchExtendable\Modification\EnsureKeyIsNotMaster;
use Croox\StatamicMeilisearchExtendable\Modification\FacetsOptionModifier;
use Croox\StatamicMeilisearchExtendable\Modification\Filtering;
use Croox\StatamicMeilisearchExtendable\Modification\IndexNamePrefix;
use Croox\StatamicMeilisearchExtendable\Modification\MeilisearchOptionModifier;
use Croox\StatamicMeilisearchExtendable\Modification\Pagination;
use Croox\StatamicMeilisearchExtendable\Modification\QueryTimeOptionModifier;
use Croox\StatamicMeilisearchExtendable\Modification\RawResults;
use Croox\StatamicMeilisearchExtendable\Modification\SearchSnippets;
use Croox\StatamicMeilisearchExtendable\Modification\SortOrderOptionModifier;
use Croox\StatamicMeilisearchExtendable\Modification\SynonymsOptionModifier;
use Croox\StatamicMeilisearchExtendable\Tests\TestCase;
use Croox\StatamicMeilisearchExtendable\Tests\Unit\Mock\MockSearchable;
use Illuminate\Support\Collection;
use Meilisearch\Client;
use Meilisearch\Endpoints\Indexes;
use Meilisearch\Exceptions\ApiException;
use Meilisearch\Search\SearchResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Statamic\Search\Searchable;
use Statamic\Search\Searchables\Provider;
use Statamic\Search\Searchables\Providers;

class IndexTest extends TestCase
{
    private Index $subject;
    private MockObject&Client $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Client::class);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('app.name', 'Statamic Meilisearch Extendable');
    }


    private function createIndex(array $config): Index
    {
        return new Index(
            $this->client,
            'test_index',
            $config,
        );
    }

    public function testUsesDefaultSetOfModifiers(): void
    {
        $index = $this->createIndex([ ]);
        $modifiers = $index->getOptionModifiers();

        $classes = array_map(
            static fn($modifier) => $modifier::class,
            $modifiers
        );

        $this->assertContains(EnsureKeyIsNotMaster::class, $classes);
        $this->assertContains(IndexNamePrefix::class, $classes);
        $this->assertContains(AdditionalQueryOptions::class, $classes);
        $this->assertContains(RawResults::class, $classes);
        $this->assertContains(SearchSnippets::class, $classes);
        $this->assertContains(FacetsOptionModifier::class, $classes);
        $this->assertContains(Filtering::class, $classes);
        $this->assertContains(Pagination::class, $classes);
        $this->assertContains(QueryTimeOptionModifier::class, $classes);
        $this->assertContains(SortOrderOptionModifier::class, $classes);
        $this->assertContains(SynonymsOptionModifier::class, $classes);
    }

    public function testAllowsSettingOfExplicitModifiers(): void
    {
        $modifier = new class extends MeilisearchOptionModifier {};
        $index = $this->createIndex([
            'meilisearch_modifiers' => [ $modifier ],
        ]);

        $modifiers = $index->getOptionModifiers();

        $this->assertCount(1, $modifiers);
        $this->assertSame($modifier, $modifiers[0]);
    }

    public function testUsesModifiersToPreProcessIndexName(): void
    {
        $modifier = new class extends MeilisearchOptionModifier {
            public function preProcessIndexName(string $name, array $config): string
            {
                return 'modified_' . $name;
            }
        };

        $index = $this->createIndex([
            'meilisearch_modifiers' => [ $modifier ],
        ]);

        $this->assertSame('modified_test_index', $index->name());
    }

    public function testUsesModifiersToPreProcessConfig(): void
    {
        $modifier = new class extends MeilisearchOptionModifier {
            public function preProcessConfiguration(array $config): array
            {
                IndexTest::assertSame([ 'foo' => 'bar' ], $config);
                return [ 'foo' => 'modified' ];
            }
        };

        $index = $this->createIndex([
            'meilisearch_modifiers' => [ $modifier ],
            'foo' => 'bar',
        ]);

        $this->assertSame([ 'foo' => 'modified' ], $index->config());
    }

    #[DataProvider('provideTrueFalse')]
    public function testUsesModifiersToValidateConfiguration(bool $valid): void
    {
        $modifier = $this->createMock(MeilisearchOptionModifier::class);
        $modifier->method('preProcessConfiguration')->willReturnArgument(0);
        $expectation = $modifier
            ->expects($this->once())
            ->method('validateConfiguration')
            ->with([ 'foo' => 'bar' ]);

        if (!$valid) {
            $expectation->willThrowException(new ConfigurationException('Invalid configuration test'));
            $this->expectException(ConfigurationException::class);
        }

        $this->createIndex([
            'meilisearch_modifiers' => [ $modifier ],
            'foo' => 'bar',
        ]);
    }

    public function testCreatesNewQuery(): void
    {
        $index = $this->createIndex([ ]);
        $query = $index->search('test query');

        $this->assertInstanceOf(Query::class, $query);
        $this->assertSame('test query', $query->getQuery());
    }

    public function testInsertsSingleDocument(): void
    {
        $index = $this->createIndex([
            'fields' => [ 'foo' ],
            'meilisearch_modifiers' => [ ],
        ]);

        $meiliIndex = $this->mockMeiliIndex();
        $meiliIndex
            ->expects($this->once())
            ->method('updateDocuments')
            ->with([
                [
                    // Only foo is inserted, since it is in the configuration
                    'foo' => 'bar',

                    // These fields are added automatically
                    'id' => 'entry---4f496a2f-90b0-4cc6-84c7-c0fe54ad83b3',
                    'reference' => 'entry::4f496a2f-90b0-4cc6-84c7-c0fe54ad83b3',
                ]
            ]);

        $searchable = new MockSearchable('entry::4f496a2f-90b0-4cc6-84c7-c0fe54ad83b3', [
            'foo' => 'bar',
            'test' => 'test',
        ]);
        $index->insert($searchable);
    }

    public function testInsertsMultipleDocuments(): void
    {
        $index = $this->createIndex([
            'fields' => [ 'foo' ],
            'meilisearch_modifiers' => [ ],
        ]);

        $meiliIndex = $this->mockMeiliIndex();
        $meiliIndex
            ->expects($this->once())
            ->method('updateDocuments')
            ->with([
                [
                    'foo' => 'bar',
                    'id' => 'entry---56ca40f8-7955-4f84-85cb-15da26605d35',
                    'reference' => 'entry::56ca40f8-7955-4f84-85cb-15da26605d35',
                ],
                [
                    'foo' => 'baz',
                    'id' => 'entry---c87255da-b087-4850-bb95-abeabac4ead6',
                    'reference' => 'entry::c87255da-b087-4850-bb95-abeabac4ead6',
                ],
            ]);

        $searchables = [
            new MockSearchable('entry::56ca40f8-7955-4f84-85cb-15da26605d35', [ 'foo' => 'bar', 'test' => 'test' ]),
            new MockSearchable('entry::c87255da-b087-4850-bb95-abeabac4ead6', [ 'foo' => 'baz', 'test' => 'test' ]),
        ];

        $index->insertMultiple(collect($searchables));
    }

    public function testDeletesFromMeilisearch(): void
    {
        $index = $this->createIndex(['meilisearch_modifiers' => [ ]]);
        $meiliIndex = $this->mockMeiliIndex();

        $meiliIndex
            ->expects($this->once())
            ->method('deleteDocument')
            ->with('entry---4f496a2f-90b0-4cc6-84c7-c0fe54ad83b3');

        $searchable = new MockSearchable('entry::4f496a2f-90b0-4cc6-84c7-c0fe54ad83b3', [ ]);
        $index->delete($searchable);
    }

    #[DataProvider('provideTrueFalse')]
    public function testChecksIfIndexExists(bool $exists): void
    {
        $index = $this->createIndex([ 'meilisearch_modifiers' => [ ]]);
        $meiliIndex = $this->mockMeiliIndex();

        $expect = $meiliIndex
            ->expects($this->once())
            ->method('fetchRawInfo');
        if (!$exists) {
            $expect->willThrowException(new ApiException(
                $this->createConfiguredMock(ResponseInterface::class, [
                    'getStatusCode' => 404,
                ]),
                [ 'message' => '' ],
                null,
            ));
        }

        $this->assertSame($exists, $index->exists());
    }

    public function testUpdatesIndex(): void
    {
        $index = $this->createIndex([
            'meilisearch_modifiers' => [ ],
            'settings' => [ 'foo' => 'bar' ],
            'searchables' => [ 'test_provider' ],
            'fields' => [ ],
        ]);
        $meiliIndex = $this->mockMeiliIndex();

        app(Providers::class)->register(new class extends Provider {
            protected static $handle = 'test_provider';
            protected static $referencePrefix = 'test_provider::';

            public function provide(): Collection
            {
                return collect([
                    new MockSearchable('test_provider::4f496a2f-90b0-4cc6-84c7-c0fe54ad83b3', [ ]),
                ]);
            }

            public function contains($searchable): bool
            {
                return false;
            }

            public function find(array $keys): Collection
            {
                return collect([]);
            }
        });

        // Old index is deleted
        $meiliIndex
            ->expects($this->once())
            ->method('delete');

        // New index is created
        $this->client
            ->expects($this->once())
            ->method('createIndex')
            ->with('test_index', ['primaryKey' => 'id']);

        // Settings are updated
        $meiliIndex
            ->expects($this->once())
            ->method('updateSettings')
            ->with(['foo' => 'bar']);

        // Searchables are inserted
        $meiliIndex
            ->expects($this->once())
            ->method('updateDocuments')
            ->with([
                [
                    'id' => 'test-provider---4f496a2f-90b0-4cc6-84c7-c0fe54ad83b3',
                    'reference' => 'test_provider::4f496a2f-90b0-4cc6-84c7-c0fe54ad83b3',
                ]
            ]);

        $index->update();
    }

    private function searchResult(array $hits): SearchResult
    {
        return new SearchResult([
            'estimatedTotalHits' => count($hits),
            'offset' => 0,
            'limit' => 20,
            'processingTimeMs' => 10,
            'query' => 'test query',
            'hits' => $hits,
        ]);
    }

    public function testPerformsSearch(): void
    {
        $index = $this->createIndex([
            'meilisearch_modifiers' => [ ],
        ]);

        $meiliIndex = $this->mockMeiliIndex();
        $meiliIndex
            ->expects($this->once())
            ->method('search')
            ->with('test query', [
                'showRankingScore' => false,
            ])->willReturn($this->searchResult([
                [
                    'id' => 'entry---4f496a2f-90b0-4cc6-84c7-c0fe54ad83b3',
                    'reference' => 'entry::4f496a2f-90b0-4cc6-84c7-c0fe54ad83b3',
                ],
            ]));

        $index->performSearch(
            $index->search('test query'),
            [
                'showRankingScore' => false,
            ]
        );
    }

    public function testUsesOptionModifierToPreProcesQueryOptions(): void
    {
        $modifier = new class extends MeilisearchOptionModifier {
            public function preProcessQueryOptions(Index $index, Query $query, array $options): array
            {
                IndexTest::assertSame([ 'foo' => 'bar' ], $options);

                return [ 'foo' => 'changed' ];
            }
        };

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test query', [ 'foo' => 'changed' ])
            ->willReturn($this->searchResult([ ]));

        $index = $this->createIndex([
            'meilisearch_modifiers' => [ $modifier ],
        ]);

        $index->performSearch(
            $index->search('test query'),
            [
                'foo' => 'bar',
            ]
        );
    }

    public function testUsesOptionModifierToPostProcessResult(): void
    {
        $modifier = new class extends MeilisearchOptionModifier {
            public function postProcessResults(Index $index, SearchResult $results, array $queryOptions): SearchResult
            {
                IndexTest::assertSame([ 'foo' => 'bar' ], $queryOptions);
                IndexTest::assertSame([ [ 'foo' => 'baz' ] ], $results->getHits());

                return $results->transformHits(function (array $hits) {
                    $hits[0]['foo'] = 'changed';
                    return $hits;
                });
            }
        };

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test query', [ 'foo' => 'bar' ])
            ->willReturn($this->searchResult([ [ 'foo' => 'baz' ] ]));

        $index = $this->createIndex([
            'meilisearch_modifiers' => [ $modifier ],
        ]);

        $results = $index->performSearch(
            $index->search('test query'),
            [
                'foo' => 'bar',
            ]
        );

        $this->assertSame([ 'foo' => 'changed' ], $results->getHits()[0]);
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
