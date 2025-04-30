<?php

namespace Croox\StatamicMeilisearch\Modification;

use Croox\StatamicMeilisearch\ConfigurationException;
use Croox\StatamicMeilisearch\Index;
use Illuminate\Support\Facades\Cache;
use Meilisearch\Exceptions\ApiException;

class EnsureKeyIsNotMaster extends MeilisearchOptionModifier
{
    public function preProcessQueryOptions(Index $index, string $searchQuery, array $options): array
    {
        $this->ensureKeyIsNotMaster($index);

        return $options;
    }

    private function ensureKeyIsNotMaster(Index $index): void
    {
        $key = config('statamic.search.drivers.meilisearch.credentials.secret');
        if ($key === null || $key === '') {
            return;
        }

        $cacheKey = 'croox_meilisearch_master_key_checked_' . $key;
        $hasChecked = Cache::get($cacheKey);
        if ($hasChecked) {
            return;
        }

        $isAdminKey = true;
        try {
            $index->client()->getKeys();
        } catch (ApiException $e) {
            $isAdminKey = $e->getCode() !== 403;
        }

        if ($isAdminKey) {
            throw new ConfigurationException('
                The MeiliSearch API key configured in .env is an admin key.
                Please generate a new key using the artisan command "php artisan croox:meilisearch:generate-api-key"
                and update your .env file.
            ');
        }

        Cache::forever($cacheKey, true);
    }
}
