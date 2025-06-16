<?php

namespace Croox\StatamicMeilisearchExtendable\Modification;

use Croox\StatamicMeilisearchExtendable\ConfigurationException;
use Croox\StatamicMeilisearchExtendable\Meilisearch\Index;
use Croox\StatamicMeilisearchExtendable\Meilisearch\Query;
use Illuminate\Contracts\Cache\Repository;
use Meilisearch\Exceptions\ApiException;

class EnsureKeyIsNotMaster extends MeilisearchOptionModifier
{
    public function __construct(
        private readonly Repository $cache,
    ) {
    }

    public function preProcessQueryOptions(Index $index, Query $query, array $options): array
    {
        $this->ensureKeyIsNotMaster($index);

        return $options;
    }

    private function ensureKeyIsNotMaster(Index $index): void
    {
        $config = $index->config();

        if (($config['meilisearch']['ensure_key_is_not_master'] ?? true) === false) {
            return;
        }

        $key = $config['credentials']['secret'] ?? null;
        if ($key === null || $key === '') {
            return;
        }

        $cacheKey = 'croox_meilisearch_master_key_checked_' . $key;
        $hasChecked = $this->cache->get($cacheKey);
        if ($hasChecked) {
            return;
        }

        $isAdminKey = true;
        $exception = null;
        try {
            $index->client()->getKeys();
        } catch (ApiException $e) {
            $exception = $e;
            $isAdminKey = $e->getCode() !== 403;
        }

        if ($isAdminKey) {
            throw new ConfigurationException('
                The MeiliSearch API key configured in .env is an admin key.
                Please generate a new key using the artisan command "php artisan croox:meilisearch:generate-api-key"
                and update your .env file.
            ', previous: $exception);
        }

        $this->cache->forever($cacheKey, true);
    }
}
