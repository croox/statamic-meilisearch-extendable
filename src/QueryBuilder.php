<?php

namespace Croox\StatamicMeilisearch;

use Illuminate\Support\Collection;
use StatamicRadPack\Meilisearch\Meilisearch\Query;

/**
 * @property Index $index
 */
class QueryBuilder extends Query
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
     * @param string $query
     * @return Collection
     */
    public function getSearchResults($query)
    {
        $results = $this->index->performSearch($this);

        return collect($results->getHits())->map(function ($result) {
            $result['search_score'] = (int) ceil($result['_rankingScore'] * 1000);

            return $result;
        });
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
