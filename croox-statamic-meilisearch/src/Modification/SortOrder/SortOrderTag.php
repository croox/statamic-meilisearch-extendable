<?php

namespace Croox\StatamicMeilisearch\Modification\SortOrder;

use Statamic\Tags\Tags;

/**
 * @api
 */
class SortOrderTag extends Tags
{
    /**
     * @var string
     * @psalm-suppress NonInvariantDocblockPropertyType
     */
    protected static $handle = 'meilisearch_sort_order';

    public function __construct(
        private readonly SortOrderOptionModifier $orderOptionModifier,
    ) {
    }


    /**
     * @return array<string, list<string>>
     */
    public function index(): array
    {
        $as = $this->params->get('as') ?: 'sort_order';

        return [ $as => $this->orderOptionModifier->getActiveSort() ];
    }
}
