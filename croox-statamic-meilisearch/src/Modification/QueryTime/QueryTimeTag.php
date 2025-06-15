<?php

namespace Croox\StatamicMeilisearch\Modification\QueryTime;

use Statamic\Tags\Tags;

/** @api */
class QueryTimeTag extends Tags
{
    /**
     * @var string
     * @psalm-suppress NonInvariantDocblockPropertyType
     */
    protected static $handle = 'meilisearch_query_time';

    public function __construct(
        private readonly QueryTimeOptionModifier $queryTime,
    ) {
    }

    public function index(): ?float
    {
        return $this->queryTime->getRuntimeMs();
    }
}
