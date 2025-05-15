<?php

namespace Croox\StatamicMeilisearch\Modification;

use Croox\StatamicMeilisearch\Index;
use Croox\StatamicMeilisearch\QueryBuilder;

class AdditionalQueryOptions extends MeilisearchOptionModifier
{
    public function preProcessQueryOptions(Index $index, QueryBuilder $query, array $options): array
    {
        return array_replace($index->config()['query_options'] ?? [], $options);
    }
}
