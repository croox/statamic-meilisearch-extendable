<?php

namespace Croox\StatamicMeilisearchExtendable\Modification;

use Croox\StatamicMeilisearchExtendable\ConfigurationException;

/**
 * By default, the name from the configuration is directly used as the index name.
 * In order to have different index names for different projects and environments, the app
 * name and environment are prefixed.
 */
class IndexNamePrefix extends MeilisearchOptionModifier
{
    private readonly string $appName;
    private readonly string $environment;

    public function __construct(
        ?string $appName = null,
        ?string $environment = null
    ) {
        $this->appName = $appName ?? config('app.name', 'Statamic');
        $this->environment = $environment ?? config('app.env', 'production');
    }

    public function validateConfiguration(array $config): void
    {
        if (
            isset($config['meilisearch']['index_name'])
            && !preg_match('/^[a-z\-_]+$/', $config['meilisearch']['index_name'])
        ) {
            throw new ConfigurationException(sprintf(
                'The croox meilisearch integration requires the index_name to only contain lowercase letters, ' .
                'dashes and underscores, but detected "%s". Please update your config file.',
                $config['meilisearch']['index_name']
            ));
        }

        if (
            !isset($config['meilisearch']['index_name'])
            && in_array($this->appName, [ 'Statamic', 'Statamic Peak', 'Laravel' ], true)
        ) {
            throw new ConfigurationException(sprintf(
                'The croox meilisearch integration requires a unique app name to be set, but detected "%s". ' .
                'Please update your .env file in order to use a custom app name.',
                $this->appName
            ));
        }
    }

    public function preProcessIndexName(string $name, array $config): string
    {
        if (isset($config['meilisearch']['index_name'])) {
            return $config['meilisearch']['index_name'];
        }

        return $this->prefixedIndexName([ $name ]);
    }

    public function prefixedIndexName(array $additionalNameParts): string
    {
        $parts = array_merge([ $this->appName, $this->environment ], $additionalNameParts);
        $parts = array_map(fn(string $part) => preg_replace('/[^a-z0-9]/', '-', strtolower($part)), $parts);
        return implode('__', $parts);
    }
}
