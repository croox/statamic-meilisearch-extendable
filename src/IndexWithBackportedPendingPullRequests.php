<?php

namespace Croox\StatamicMeilisearch;

use Illuminate\Support\Str;
use Meilisearch\Endpoints\Indexes;
use Statamic\Contracts\Search\Searchable;
use Statamic\Search\Documents;

class IndexWithBackportedPendingPullRequests extends \Elvenstar\StatamicMeiliSearch\MeiliSearch\Index
{
    /**
     * This method can be removed once https://github.com/elvenstar/statamic-meilisearch/pull/18 is merged.
     * @TODO remove method
     *
     * @param Searchable $document
     */
    public function delete($document): void
    {
        $this->getIndex()->deleteDocument($this->getSafeDocmentID($document->getSearchReference()));
    }

    /**
     * This method can be removed once https://github.com/elvenstar/statamic-meilisearch/pull/19 is merged.
     * @TODO remove method
     *
     * @param Searchable $document
     */
    public function insert($document): void
    {
        $fields = array_merge(
            $this->searchables()->fields($document),
            $this->getDefaultFields($document),
        );
        $this->getIndex()->updateDocuments([$fields]);
    }

    /**
     * This method can be removed once https://github.com/elvenstar/statamic-meilisearch/pull/19 is merged.
     * @TODO remove method
     */
    private function getIndex(): Indexes
    {
        return $this->client->index($this->name);
    }

    /**
     * This method can be removed once https://github.com/elvenstar/statamic-meilisearch/pull/19 is merged.
     * @TODO remove method
     */
    public function update(): self
    {
        $this->deleteIndex();
        $this->createIndex();

        // Prepare documents for update
        $searchables = $this->searchables()->all()->map(function (Searchable $entry) {
            return array_merge(
                $this->searchables()->fields($entry),
                $this->getDefaultFields($entry),
            );
        });

        // Update documents
        $documents = new Documents($searchables);
        $this->insertDocuments($documents);

        return $this;
    }

    /**
     * This method can be removed once https://github.com/elvenstar/statamic-meilisearch/pull/19 is merged.
     * @TODO remove method
     * @return array<string, string>
     */
    private function getDefaultFields(Searchable $entry): array
    {
        return [
            'id' => $this->getSafeDocmentID($entry->getSearchReference()),
            'reference' => $entry->getSearchReference(),
        ];
    }

    /**
     * This method can be removed once https://github.com/elvenstar/statamic-meilisearch/pull/19 is merged.
     * @TODO remove method
     */
    private function getSafeDocmentID(string $entryReference): string
    {
        return Str::of($entryReference)
            ->explode('::')
            ->map(function ($part) {
                return Str::slug($part);
            })
            ->implode('---');
    }
}
