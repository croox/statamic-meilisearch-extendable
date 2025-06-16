<?php

namespace Croox\StatamicMeilisearchExtendable\Modification;

use Croox\StatamicMeilisearchExtendable\Meilisearch\Index;
use Croox\StatamicMeilisearchExtendable\Meilisearch\Query;

class AdditionalQueryOptions extends MeilisearchOptionModifier
{
    public function preProcessQueryOptions(Index $index, Query $query, array $options): array
    {
        return array_replace($index->config()['meilisearch']['query_options'] ?? [], $options);
    }
}
