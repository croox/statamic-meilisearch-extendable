<?php

namespace Croox\StatamicMeilisearch;

use Elvenstar\StatamicMeiliSearch\StatamicMeiliSearchServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Meilisearch\Client;
use Statamic\Facades\Search;
use Statamic\Providers\AddonServiceProvider;

/**
 * @api
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon(): void
    {
        // Ensure the MeiliSearch addon is loaded before this addon
        $this->app->register(StatamicMeiliSearchServiceProvider::class);

        $this->registerIndex();
        $this->commands([
            GenerateApiKeyCommand::class,
        ]);
    }

    private function registerIndex(): void
    {
        // TODO: This can be simplified once https://github.com/elvenstar/statamic-meilisearch/pull/20 is merged
        //       until that time, the much larger block below is required.
        // $this->app->bind(\Elvenstar\StatamicMeiliSearch\MeiliSearch\Index::class, Index::class);

        Search::extend('meilisearch', function (Application $app, array $config, string $name) {
            $credentials = $config['credentials'];
            $url = $credentials['url'];
            $masterKey = $credentials['secret'];

            $client = new Client($url, $masterKey);

            return new Index($client, $name, $config);
        });
    }
}
