<?php

namespace Croox\StatamicMeilisearch;

use Croox\StatamicMeilisearch\Modification\MeilisearchOptionModifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Meilisearch\Client;
use Meilisearch\Exceptions\ApiException;
use Meilisearch\Search\SearchResult;
use Statamic\Search\Result;
use StatamicRadPack\Meilisearch\Meilisearch\Index as BaseIndex;

class Index extends BaseIndex
{
    /** @var list<MeilisearchOptionModifier> */
    private array $modifiers = [ ];

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(Client $client, string $name, array $config)
    {
        [ $name, $config ] = $this->initializeModifiers($name, $config);
        parent::__construct($client, $name, $config);
    }

    /**
     * @param string $query
     * @return QueryBuilder
     */
    public function search($query)
    {
        return (new QueryBuilder($this))->query($query);
    }

    /** @return list{string, array} */
    private function initializeModifiers(string $name, array $config): array
    {
        foreach ($config['meilisearch_modifiers'] ?? Meilisearch::DEFAULT_MODIFIERS as $modifier) {
            /** @psalm-suppress PropertyTypeCoercion */
            $this->modifiers[] = app($modifier);
        }
        unset($config['meilisearch_modifiers']);

        foreach ($this->modifiers as $modifier) {
            if (!($modifier instanceof MeilisearchOptionModifier)) {
                throw new ConfigurationException(sprintf(
                    'The modifier "%s" must extend the %s.',
                    $modifier::class,
                    MeilisearchOptionModifier::class
                ));
            }

            $name = $modifier->preProcessIndexName($name, $config);
            $config = $modifier->preProcessConfiguration($config);
        }

        if (!Cache::get('croox_meilisearch_config_validated')) {
            foreach ($this->modifiers as $modifier) {
                $modifier->validateConfiguration($config);
            }
            Cache::forever('croox_meilisearch_config_validated', true);
        }

        return [ $name, $config ];
    }

    public function performSearch(
        QueryBuilder $query,
        array $options = [ 'showRankingScore' => true ],
    ): SearchResult {
        foreach ($this->modifiers as $modifier) {
            $options = $modifier->preProcessQueryOptions($this, $query, $options);
        }


        foreach (array_keys($options) as $key) {
            if (str_starts_with($key, '_')) {
                unset($options[$key]);
            }
        }
        unset($options['meilisearch']);

        $result = $this->client->index($this->name)->search($query->getQuery(), $options);

        foreach ($this->modifiers as $modifier) {
            $result = $modifier->postProcessResults($this, $result, $options);
        }

        return $result;
    }

    /** @return array<string, mixed> */
    public function extraAugmentedResultData(Result $result)
    {
        $extra = parent::extraAugmentedResultData($result);
        $rawResult = $result->getRawResult();

        foreach ($this->modifiers as $modifier) {
            foreach ($modifier->extractExtraResultDataFromRawResult($rawResult) as $key => $value) {
                $extra[$key] = $value;
            }
        }

        foreach ($rawResult as $key => $value) {
            if (!$result->getSearchable()->getSearchValue($key) && !isset($extra[$key])) {
                $extra[$key] = $value;
            }
        }

        return $extra;
    }
}
