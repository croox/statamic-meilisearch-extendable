<?php

namespace Croox\StatamicMeilisearch\Modification\QueryTime;

use Croox\StatamicMeilisearch\Index;
use Croox\StatamicMeilisearch\QueryBuilder;
use Meilisearch\Search\SearchResult;
use Croox\StatamicMeilisearch\Modification\MeilisearchOptionModifier;

class QueryTimeOptionModifier extends MeilisearchOptionModifier
{
    private ?float $start = null;
    private ?float $end = null;

    public function getRuntimeMs(): ?float
    {
        if ($this->start === null || $this->end === null) {
            return null;
        }

        return ($this->end - $this->start) * 1000.0;
    }

    public function preProcessQueryOptions(Index $index, QueryBuilder $query, array $options): array
    {
        $this->start = microtime(true);

        return $options;
    }

    public function postProcessResults(Index $index, SearchResult $results, array $queryOptions): SearchResult
    {
        $this->end = microtime(true);

        return $results;
    }
}
