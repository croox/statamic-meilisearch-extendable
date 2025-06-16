<?php

namespace Croox\StatamicMeilisearchExtendable\Meilisearch;

use Illuminate\Support\Collection;
use Statamic\Search\QueryBuilder;

/**
 * @property Index $index
 */
class Query extends QueryBuilder
{
    public function getQuery(): string
    {
        return $this->query;
    }

    protected function getCountForPagination(): ?int
    {
        $result = $this->index->performSearch($this, [
            'hitsPerPage' => 1,
            '_is_count' => true,
        ]);

        return $result->getTotalHits();
    }

    /**
     * @return Collection
     * @api
     */
    public function getSearchResults()
    {
        $results = array_values($this->index->performSearch($this)->getHits());
        $results = $this->addSearchScore($results);

        return collect($results);
    }

    /**
     * By default, statamic does a hardcoded descending sort by search_score. In order to preserve
     * the sorting returned by meilisearch, we add a 'fake' search_score that is already descending.
     *
     * @param list<array<string, mixed>> $results
     * @return list<array<string, mixed>>
     */
    private function addSearchScore(array $results): array
    {
        $withScore = [ ];

        foreach ($results as $i => $result) {
            $result['search_score'] = count($results) - $i;
            $withScore[] = $result;
        }

        return $withScore;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setWheres(array $where): void
    {
        $this->wheres = $where;
    }

    public function getWheres(): array
    {
        return $this->wheres;
    }
}
