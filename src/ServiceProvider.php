<?php

namespace Croox\StatamicMeilisearch;

use StatamicRadPack\Meilisearch\ServiceProvider as StatamicMeiliSearchServiceProvider;
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

        $this->app->bind(\StatamicRadPack\Meilisearch\Meilisearch\Index::class, Index::class);
        $this->commands([
            GenerateApiKeyCommand::class,
        ]);
    }
}
