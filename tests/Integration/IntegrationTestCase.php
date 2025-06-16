<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Integration;

use Croox\StatamicMeilisearchExtendable\Meilisearch\Index;
use Croox\StatamicMeilisearchExtendable\Tests\TestCase;
use Statamic\Facades;

class IntegrationTestCase extends TestCase
{
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

        // add index
        $app['config']->set('statamic.search.indexes.meilisearch_index', [
            'driver' => 'meilisearch',
            'searchables' => ['collection:pages'],
            'meilisearch' => [
                'ensure_key_is_not_master' => false, // disable this for testing
            ]
        ]);
    }

    protected function getIndex(): Index
    {
        return Facades\Search::index('meilisearch_index');
    }
}
