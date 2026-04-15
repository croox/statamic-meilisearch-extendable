<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Integration;

use Croox\StatamicMeilisearchExtendable\Meilisearch\Index;
use Croox\StatamicMeilisearchExtendable\Tests\TestCase;
use Statamic\Facades;

class IntegrationTestCase extends TestCase
{
    protected Index $index;

    public function setUp(): void
    {
        parent::setUp();

        $this->index = Facades\Search::index('meilisearch_index');
    }

    public function tearDown(): void
    {
        $this->index->deleteIndex();
        parent::tearDown();
    }
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('app.name', 'Some Statamic Site');

        // add driver
        $app['config']->set('statamic.search.drivers.meilisearch', [
            'credentials' => [
                'url' => env('MEILISEARCH_URL', 'http://localhost:7700'),
                'secret' => env('MEILISEARCH_KEY', 'masterKey'),
            ],
        ]);

        $app['config']->set('statamic.search.indexes.cp', [
            'driver' => 'null',
        ]);

        $app['config']->set('statamic.search.indexes.meilisearch_index', [
            'driver' => 'meilisearch',
            'searchables' => ['collection:pages'],
            'meilisearch' => [
                'ensure_key_is_not_master' => false, // disable this for testing
            ]
        ]);
    }
}
