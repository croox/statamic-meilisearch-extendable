<?php

namespace Croox\StatamicMeilisearch;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Meilisearch\Client;

class Index extends IndexWithBackportedPendingPullRequests
{
    public function __construct(Client $client, string $name, array $config)
    {
        parent::__construct($client, $this->prefixIndexNameWithEnvironmentSpecifics($name, $config), $config);
        $this->validateConfiguration($config);
    }

    /**
     * @param string $query
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $options
     * @return Collection
     */
    public function searchUsingApi($query, $filters = [], $options = [])
    {
        $filters = array_replace($this->config['query_options'] ?? [], $filters);
        return parent::searchUsingApi($query, $filters, $options);
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

        $appName = config('app.name');
        $environment = config('app.env');

        $parts = [$appName, $environment, $name];
        $parts = array_map(fn(string $part) => preg_replace('/[^a-z0-9]/', '-', strtolower($part)), $parts);
        return implode('__', $parts);
    }

    private function validateConfiguration(array $config): void
    {
        if (Cache::get('croox_meilisearch_config_validated')) {
            return;
        }

        $appName = config('app.name');
        if ($appName === 'Statamic' || $appName === 'Statamic Peak') {
            throw new ConfigurationException(sprintf(
                'The croox meilisearch integration requires a unique app name to be set, but detected "%s". Please update your .env file in order to use a custom app name.',
                $appName
            ));
        }

        if (isset($config['index_name']) && !preg_match('/^[a-z\-_]$/', $config['index_name'])) {
            throw new ConfigurationException(sprintf(
                'The croox meilisearch integration requires the index_name to only contain lowercase letters, dashes and underscores, but detected "%s". Please update your config file.',
                $config['index_name']
            ));
        }

        Cache::forever('croox_meilisearch_config_validated', true);
    }
}
