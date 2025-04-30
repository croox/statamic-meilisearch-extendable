<?php

namespace Croox\StatamicMeilisearch\Modification;

use Croox\StatamicMeilisearch\Index;

class AdditionalQueryOptions extends MeilisearchOptionModifier
{
    public function preProcessQueryOptions(Index $index, string $searchQuery, array $options): array
    {
        return array_replace($index->config()['query_options'] ?? [], $options);
    }
}