<?php

namespace Croox\StatamicMeilisearch;

use Croox\StatamicMeilisearch\Modification\AdditionalQueryOptions;
use Croox\StatamicMeilisearch\Modification\EnsureKeyIsNotMaster;
use Croox\StatamicMeilisearch\Modification\IndexNamePrefix;
use Croox\StatamicMeilisearch\Modification\MeilisearchOptionModifier;
use Croox\StatamicMeilisearch\Modification\RawResults;
use Croox\StatamicMeilisearch\Modification\SearchSnippets;

class Meilisearch
{
    /** @var list<class-string<MeilisearchOptionModifier>> */
    public const DEFAULT_MODIFIERS = [
        EnsureKeyIsNotMaster::class,
        IndexNamePrefix::class,
        AdditionalQueryOptions::class,
        RawResults::class,
        SearchSnippets::class,
    ];
}
