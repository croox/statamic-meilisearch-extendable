<?php

namespace Croox\StatamicMeilisearchExtendable\Meilisearch;

use Croox\StatamicMeilisearchExtendable\ConfigurationException;
use Croox\StatamicMeilisearchExtendable\Meilisearch;
use Croox\StatamicMeilisearchExtendable\Modification\MeilisearchOptionModifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Meilisearch\Client;
use Meilisearch\Endpoints\Indexes;
use Meilisearch\Exceptions\ApiException;
use Meilisearch\Search\SearchResult;
use Statamic\Contracts\Search\Searchable;
use Statamic\Search\Documents;
use Statamic\Search\Index as BaseIndex;
use Statamic\Search\Result;

/** @api */
class Index extends BaseIndex
{
    protected Client $client;

    /** @var list<MeilisearchOptionModifier> */
    private array $modifiers = [ ];

    public function __construct(Client $client, string $name, array $config, ?string $locale = null)
    {
        [ $name, $config ] = $this->initializeModifiers($name, $config);

        $this->client = $client;

        parent::__construct($name, $config, $locale);
    }

    /**
     * @return list{string, array}
     * @throws ConfigurationException
     */
    private function initializeModifiers(string $name, array $config): array
    {
        foreach ($config['meilisearch_modifiers'] ?? Meilisearch::DEFAULT_MODIFIERS as $modifier) {
            /** @psalm-suppress PropertyTypeCoercion */
            $this->modifiers[] = ($modifier instanceof MeilisearchOptionModifier) ? $modifier : app($modifier);
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

    /** @param string $query */
    public function search($query): Query
    {
        return (new Query($this))->query($query);
    }

    /** @param Searchable $document */
    public function insert($document): self
    {
        return $this->insertMultiple(collect([$document]));
    }

    /** @param Collection<int, Searchable> $documents */
    public function insertMultiple($documents): self
    {
        $documents
            ->chunk(config('statamic-meilisearch.insert_chunk_size', 100))
            ->each(function ($documents) {
                $documents = $documents
                    ->map(fn ($document) => array_merge(
                        $this->searchables()->fields($document),
                        $this->getDefaultFields($document),
                    ))
                    ->values()
                    ->toArray();

                $this->insertDocuments(new Documents($documents));
            });

        return $this;
    }

    /** @param mixed $document */
    public function delete($document): void
    {
        $this->getIndex()->deleteDocument($this->getSafeDocumentID($document->getSearchReference()));
    }

    public function exists(): bool
    {
        try {
            $this->getIndex()->fetchRawInfo();

            return true;
        } catch (ApiException $e) {
            return false;
        }
    }

    protected function insertDocuments(Documents $documents): void
    {
        $this->getIndex()->updateDocuments($documents->all());
    }

    protected function deleteIndex(): void
    {
        try {
            $this->getIndex()->delete();
        } catch (ApiException $e) {
            $this->handlemeilisearchException($e, 'deleteIndex');
        }
    }

    protected function createIndex(): void
    {
        try {
            $this->client->createIndex($this->name, ['primaryKey' => 'id']);

            if (! isset($this->config['settings'])) {
                return;
            }

            $this->getIndex()->updateSettings($this->config['settings']);
            $this->getIndex()->updatePagination($this->config['pagination'] ?? ['maxTotalHits' => 1000000]);
        } catch (ApiException $e) {
            $this->handlemeilisearchException($e, 'createIndex');
        }
    }

    public function update(): self
    {
        $this->deleteIndex();
        $this->createIndex();

        $this->searchables()->lazy()->each(fn (mixed $searchables) => $this->insertMultiple($searchables));

        return $this;
    }

    public function performSearch(
        Query $query,
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

        $result = $this->getIndex()->search($query->getQuery(), $options);

        foreach ($this->modifiers as $modifier) {
            $result = $modifier->postProcessResults($this, $result, $options);
        }

        return $result;
    }

    private function getIndex(): Indexes
    {
        return $this->client->index($this->name);
    }

    private function getDefaultFields(Searchable $entry): array
    {
        return [
            'id' => $this->getSafeDocumentID($entry->getSearchReference()),
            'reference' => $entry->getSearchReference(),
        ];
    }

    /**
     * Custom error parsing for Meilisearch exceptions.
     *
     * @return void
     */
    private function handleMeilisearchException(ApiException $e, string $method): void
    {
        // Ignore if already created.
        if ($e->errorCode === 'index_already_exists' && $method === 'createIndex') {
            return;
        }

        // Ignore if not found.
        if ($e->errorCode === 'index_not_found' && $method === 'deleteIndex') {
            return;
        }

        throw $e;
    }

    /**
     * Get the document ID for the given entry.
     * As a document id is only allowed to be an integer or string composed only of alphanumeric characters
     * (a-z A-Z 0-9), hyphens (-), and underscores (_) we need to make sure that the ID is safe to use.
     * More under https://docs.meilisearch.com/reference/api/error_codes.html#invalid-document-id
     */
    private function getSafeDocumentID(string $entryReference): string
    {
        return Str::of($entryReference)
            ->explode('::')
            ->map(function ($part) {
                return Str::slug($part);
            })
            ->implode('---');
    }

    public function getCount(): int
    {
        return $this->getIndex()->stats()['numberOfDocuments'] ?? 0;
    }

    public function client(): Client
    {
        return $this->client;
    }

    /** @return array<string, mixed> */
    public function extraAugmentedResultData(Result $result): array
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

    /** @return list<MeilisearchOptionModifier> */
    public function getOptionModifiers(): array
    {
        return $this->modifiers;
    }

    public function getMetadata(): array
    {
        $metadata = [ ];

        foreach ($this->modifiers as $modifier) {
            foreach ($modifier->getMetadata() as $key => $value) {
                $metadata[$key] = $value;
            }
        }

        return $metadata;
    }
}
