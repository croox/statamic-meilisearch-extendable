<?php

namespace Croox\StatamicMeilisearch;

use Croox\StatamicMeilisearch\Modification\Facets\FacetsOptionModifier;
use Croox\StatamicMeilisearch\Modification\Facets\FacetsTag;
use Croox\StatamicMeilisearch\Modification\QueryTime\QueryTimeOptionModifier;
use Croox\StatamicMeilisearch\Modification\QueryTime\QueryTimeTag;
use StatamicRadPack\Meilisearch\ServiceProvider as StatamicMeiliSearchServiceProvider;
use Statamic\Providers\AddonServiceProvider;

/**
 * @api
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ServiceProvider extends AddonServiceProvider
{
    public function register()
    {
        $this->app->singleton(FacetsOptionModifier::class);
        $this->app->singleton(QueryTimeOptionModifier::class);
    }


    public function bootAddon(): void
    {
        // Ensure the MeiliSearch addon is loaded before this addon
        $this->app->register(StatamicMeiliSearchServiceProvider::class);

        $this->app->bind(\StatamicRadPack\Meilisearch\Meilisearch\Index::class, Index::class);
        $this->commands([
            GenerateApiKeyCommand::class,
        ]);

        // Saves result for later use
        FacetsTag::register();
        QueryTimeTag::register();
    }
}
