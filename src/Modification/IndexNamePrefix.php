<?php

namespace Croox\StatamicMeilisearch\Modification;

use Croox\StatamicMeilisearch\ConfigurationException;

/**
 * By default, the name from the configuration is directly used as the index name.
 * In order to have different index names for different projects and environments, the app
 * name and environment are prefixed.
 */
class IndexNamePrefix extends MeilisearchOptionModifier
{
    public function validateConfiguration(array $config): void
    {
        $appName = config('app.name');
        if ($appName === 'Statamic' || $appName === 'Statamic Peak') {
            throw new ConfigurationException(sprintf(
                'The croox meilisearch integration requires a unique app name to be set, but detected "%s". ' .
                'Please update your .env file in order to use a custom app name.',
                $appName
            ));
        }

        if (isset($config['index_name']) && !preg_match('/^[a-z\-_]$/', $config['index_name'])) {
            throw new ConfigurationException(sprintf(
                'The croox meilisearch integration requires the index_name to only contain lowercase letters, ' .
                'dashes and underscores, but detected "%s". Please update your config file.',
                $config['index_name']
            ));
        }
    }

    public function preProcessIndexName(string $name, array $config): string
    {
        if (isset($config['index_name'])) {
            return $config['index_name'];
        }

        return self::prefixedIndexName([ $name ]);
    }

    public static function prefixedIndexName(array $additionalNameParts): string
    {
        $appName = config('app.name');
        $environment = config('app.env');

        $parts = array_merge([ $appName, $environment ], $additionalNameParts);
        $parts = array_map(fn(string $part) => preg_replace('/[^a-z0-9]/', '-', strtolower($part)), $parts);
        return implode('__', $parts);
    }
}
