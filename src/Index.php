<?php

namespace Croox\StatamicMeilisearch;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Meilisearch\Client;
use Meilisearch\Endpoints\Keys;
use Meilisearch\Exceptions\ApiException;
use Statamic\Search\Result;

class Index extends IndexWithBackportedPendingPullRequests
{
    private const DEFAULT_SNIPPET_LENGTH = 100;

    public function __construct(Client $client, string $name, array $config)
    {
        parent::__construct($client, $this->prefixIndexNameWithEnvironmentSpecifics($name, $config), $config);
        $this->validateConfiguration($config);
    }

    private function ensureKeyIsNotMaster(): void
    {
        $key = config('statamic.search.drivers.meilisearch.credentials.secret');
        if (!$key) {
            return;
        }

        $cacheKey = 'croox_meilisearch_master_key_checked_' . $key;
        $hasChecked = Cache::get($cacheKey);
        if ($hasChecked) {
            return;
        }

        $isAdminKey = true;
        try {
            $this->client->getKeys();
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

    /**
     * @param string $query
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $options
     * @return Collection
     */
    public function searchUsingApi($query, $filters = [], $options = [])
    {
        $this->ensureKeyIsNotMaster();

        $filters = $this->preProcessQueryOptions($filters);
        $results = parent::searchUsingApi($query, $filters, $options);
        return $results->map(fn(array $result) => $this->postProcessQueryResult($result, $filters));
    }

    private function preProcessQueryOptions(array $queryOptions): array
    {
        $queryOptions = array_replace($this->config['query_options'] ?? [], $queryOptions);

        if ($this->getSnippetLength($queryOptions) !== null) {
            $queryOptions['attributesToHighlight'] = $queryOptions['attributesToHighlight'] ?? [ '*' ];
            $queryOptions['highlightPreTag'] = $queryOptions['highlightPreTag'] ?? '<mark>';
            $queryOptions['highlightPostTag'] = $queryOptions['highlightPostTag'] ?? '</mark>';
        }

        return $queryOptions;
    }

    private function postProcessQueryResult(array $result, array $queryOptions): array
    {
        $snippetLength = $this->getSnippetLength($queryOptions);
        if ($snippetLength !== null) {
            $extractor = new SnippetExtractor(
                $snippetLength,
                $queryOptions['highlightPreTag'],
                $queryOptions['highlightPostTag']
            );
            $result['search_snippets'] = $extractor->extractSearchSnippetsFromMeilisearchResult(
                $result['_formatted'] ?? [],
            );
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $queryOptions
     * @return int<1, max>
     */
    private function getSnippetLength(array $queryOptions): ?int
    {
        if (empty($this->config['snippet_length']) && empty($queryOptions['attributesToHighlight'])) {
            return null;
        }

        $length = $this->config['snippet_length'] ?? self::DEFAULT_SNIPPET_LENGTH;
        if ($length < 1) {
            return null;
        }

        return $length;
    }

    public static function prefixedIndexName(array $additionalNameParts): string
    {
        $appName = config('app.name');
        $environment = config('app.env');

        $parts = array_merge([ $appName, $environment ], $additionalNameParts);
        $parts = array_map(fn(string $part) => preg_replace('/[^a-z0-9]/', '-', strtolower($part)), $parts);
        return implode('__', $parts);
    }

    /**
     * By default, the name from the configuration is directly used as the index name.
     * In order to have different index names for different projects and environments, the app
     * name and environment are prefixed.
     * @throws ConfigurationException
     */
    private function prefixIndexNameWithEnvironmentSpecifics(string $name, array $config): string
    {
        if (isset($config['index_name'])) {
            return $config['index_name'];
        }

        return self::prefixedIndexName([ $name ]);
    }

    private function validateConfiguration(array $config): void
    {
        if (Cache::get('croox_meilisearch_config_validated')) {
            return;
        }

        $appName = config('app.name');
        if ($appName === 'Statamic' || $appName === 'Statamic Peak') {
            throw new ConfigurationException(
                sprintf(
                    'The croox meilisearch integration requires a unique app name to be set, but detected "%s". Please update your .env file in order to use a custom app name.',
                    $appName
                )
            );
        }

        if (isset($config['index_name']) && !preg_match('/^[a-z\-_]$/', $config['index_name'])) {
            throw new ConfigurationException(
                sprintf(
                    'The croox meilisearch integration requires the index_name to only contain lowercase letters, dashes and underscores, but detected "%s". Please update your config file.',
                    $config['index_name']
                )
            );
        }

        Cache::forever('croox_meilisearch_config_validated', true);
    }

    /** @return array<string, mixed> */
    public function extraAugmentedResultData(Result $result)
    {
        $extra = parent::extraAugmentedResultData($result);
        $extra['rawResult'] = $result->getRawResult();
        if (isset($extra['rawResult']['search_snippets'])) {
            $extra['search_snippets'] = $extra['rawResult']['search_snippets'];
        }

        return $extra;
    }
}
