<?php

namespace Croox\StatamicMeilisearchExtendable\Modification;

use Croox\StatamicMeilisearchExtendable\ConfigurationException;
use Croox\StatamicMeilisearchExtendable\Meilisearch\Index;
use Croox\StatamicMeilisearchExtendable\Meilisearch\Query;

class Pagination extends MeilisearchOptionModifier
{
    public function preProcessConfiguration(array $config): array
    {
        $paginationType = $config['meilisearch']['pagination']['type'] ?? 'statamic';
        $filteringType = $config['meilisearch']['filtering']['type'] ?? 'statamic';
        if ($paginationType === 'meilisearch' && $filteringType !== 'meilisearch') {
            throw new ConfigurationException('Pagination type "meilisearch" requires Filtering type "meilisearch".');
        }

        return $config;
    }

    /**
     * Pre-process the query options before sending them to Meilisearch.
     * This method is called on every search request.
     */
    public function preProcessQueryOptions(Index $index, Query $query, array $options): array
    {
        $config = $index->config();
        $type = (string) ($config['meilisearch']['pagination']['type'] ?? 'statamic');
        $statamicHits = (int) ($config['meilisearch']['pagination']['statamic_hits'] ?? 1000);

        $isCount = (bool) ($options['_is_count'] ?? false);

        if ($type !== 'meilisearch') {
            $options['hitsPerPage'] = $statamicHits;
            return $options;
        }

        if ($isCount) {
            return $options;
        }

        $offset = $query->getOffset();
        $limit = $query->getLimit();
        if ($offset !== null && $limit !== null) {
            $options['offset'] = $offset;
            $options['limit'] = $limit;
            $query->offset(null);
            $query->limit(null);
        }

        return $options;
    }
}
