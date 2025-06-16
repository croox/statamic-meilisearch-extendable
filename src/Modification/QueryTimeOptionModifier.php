<?php

namespace Croox\StatamicMeilisearchExtendable\Modification;

use Croox\StatamicMeilisearchExtendable\Meilisearch\Index;
use Croox\StatamicMeilisearchExtendable\Meilisearch\Query;
use Meilisearch\Search\SearchResult;
use Croox\StatamicMeilisearchExtendable\Modification\MeilisearchOptionModifier;

class QueryTimeOptionModifier extends MeilisearchOptionModifier
{
    private ?float $start = null;
    private ?float $end = null;

    public function preProcessQueryOptions(Index $index, Query $query, array $options): array
    {
        $this->start = microtime(true);

        return $options;
    }

    public function postProcessResults(Index $index, SearchResult $results, array $queryOptions): SearchResult
    {
        $this->end = microtime(true);

        return $results;
    }

    public function getMetadata(): array
    {
        return [
            'runtime_ms' => $this->getRuntimeMs(),
        ];
    }

    private function getRuntimeMs(): ?float
    {
        if ($this->start === null || $this->end === null) {
            return null;
        }

        return ($this->end - $this->start) * 1000.0;
    }
}
