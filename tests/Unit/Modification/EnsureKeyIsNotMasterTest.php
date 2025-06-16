<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Modification;

use Croox\StatamicMeilisearchExtendable\ConfigurationException;
use Croox\StatamicMeilisearchExtendable\Modification\EnsureKeyIsNotMaster;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Meilisearch\Exceptions\ApiException;
use Meilisearch\Search\SearchResult;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

class EnsureKeyIsNotMasterTest extends OptionModifierTestCase
{
    #[DataProvider('provideTrueFalse')]
    public function testChecksIfKeyIsAdmin(bool $isAdmin): void
    {
        $this->mockMeiliIndex()
            ->method('search')
            ->willReturn($this->createMock(SearchResult::class));

        $expectation = $this->client
            ->expects($this->once())
            ->method('getKeys');

        if ($isAdmin) {
            $this->expectException(ConfigurationException::class);
        } else {
            $expectation->willThrowException(new ApiException(
                $this->createConfiguredMock(ResponseInterface::class, [
                    'getStatusCode' => 403,
                ]),
                [ 'message' => '' ]
            ));
        }

        $index = $this->createIndex(new EnsureKeyIsNotMaster(new Repository(new ArrayStore())), [
            'credentials' => [
                'secret' => 'some_secret',
            ]
        ]);
        $index->performSearch($index->search('test'), [ ]);
    }

    public function testDoesNotCheckIfNoKeyConfigurred(): void
    {
        $modifier = new EnsureKeyIsNotMaster(
            new Repository(new ArrayStore()),
            [ ],
        );

        $this->client
            ->expects($this->never())
            ->method('getKeys');

        $this->mockMeiliIndex()
            ->method('search')
            ->willReturn($this->createMock(SearchResult::class));

        $index = $this->createIndex($modifier, [ ]);
        $index->performSearch($index->search('test'), [ ]);
    }

    public function testDoesNotCheckMultipleTimesForSameKey(): void
    {
        $cache = new Repository(new ArrayStore());
        $modifier = new EnsureKeyIsNotMaster($cache);

        // We expect the key to be checked once
        $this->client
            ->expects($this->once())
            ->method('getKeys')
            ->willThrowException(new ApiException(
                $this->createConfiguredMock(ResponseInterface::class, [
                    'getStatusCode' => 403,
                ]),
                [ 'message' => '' ]
            ));


        $this->mockMeiliIndex()
            ->method('search')
            ->willReturn($this->createMock(SearchResult::class));

        $index = $this->createIndex($modifier, [
            'credentials' => [
                'secret' => 'some_secret',
            ],
        ]);

        // First search should trigger the key check
        $index->performSearch($index->search('test'), [ ]);
        // Second search should not trigger the key check again
        $index->performSearch($index->search('test'), [ ]);
    }
}
