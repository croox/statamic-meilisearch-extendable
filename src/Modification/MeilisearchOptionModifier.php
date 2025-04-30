<?php

namespace Croox\StatamicMeilisearch\Modification;

use Croox\StatamicMeilisearch\ConfigurationException;
use Croox\StatamicMeilisearch\Index;
use Illuminate\Support\Collection;
use Meilisearch\Search\SearchResult;

/**
 * Base class that can be extended in order to modify the options passed to Meilisearch in various stages.
 * @api
 */
abstract class MeilisearchOptionModifier
{
    public function preProcessIndexName(string $name, array $config): string
    {
        return $name;
    }

    /** @param array<string, mixed> $config */
    public function preProcessConfiguration(array $config): array
    {
        return $config;
    }

    /**
     * @param array<string, mixed> $config
     * @throws ConfigurationException
     */
    public function validateConfiguration(array $config): void
    {
    }

    /**
     * Pre-process the query options before sending them to Meilisearch.
     * This method is called on every search request.
     */
    public function preProcessQueryOptions(Index $index, string $searchQuery, array $options): array
    {
        return $options;
    }

    /**
     * Pre-process the index options before creating the index in meilisearch.
     * @psalm-suppress PossiblyUnusedParam
     */
    public function preProcessIndexOptions(Index $index, array $options): array
    {
        return $options;
    }


    public function postProcessResults(Index $index, SearchResult $results, array $queryOptions): SearchResult
    {
        return $results;
    }

    /**
     * Allows you to extract and process extra data from the raw meilisearch result, that will be available in the
     * template.
     * @param array $rawResult
     * @return array
     */
    public function extractExtraResultDataFromRawResult(array $rawResult): array
    {
        return [ ] ;
    }
}
