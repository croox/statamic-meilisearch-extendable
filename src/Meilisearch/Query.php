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
        $results = $this->index->performSearch($this);

        return collect($results->getHits());
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
