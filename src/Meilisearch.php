<?php

namespace Croox\StatamicMeilisearchExtendable;

use Croox\StatamicMeilisearchExtendable\Modification\AdditionalQueryOptions;
use Croox\StatamicMeilisearchExtendable\Modification\EnsureKeyIsNotMaster;
use Croox\StatamicMeilisearchExtendable\Modification\FacetsOptionModifier;
use Croox\StatamicMeilisearchExtendable\Modification\Filtering;
use Croox\StatamicMeilisearchExtendable\Modification\IndexNamePrefix;
use Croox\StatamicMeilisearchExtendable\Modification\MeilisearchOptionModifier;
use Croox\StatamicMeilisearchExtendable\Modification\Pagination;
use Croox\StatamicMeilisearchExtendable\Modification\QueryTimeOptionModifier;
use Croox\StatamicMeilisearchExtendable\Modification\RawResults;
use Croox\StatamicMeilisearchExtendable\Modification\SearchSnippets;
use Croox\StatamicMeilisearchExtendable\Modification\SortOrderOptionModifier;
use Croox\StatamicMeilisearchExtendable\Modification\SynonymsOptionModifier;

class Meilisearch
{
    /** @var list<class-string<MeilisearchOptionModifier>> */
    public const DEFAULT_MODIFIERS = [
        EnsureKeyIsNotMaster::class,
        IndexNamePrefix::class,
        AdditionalQueryOptions::class,
        RawResults::class,
        SearchSnippets::class,
        FacetsOptionModifier::class,
        Filtering::class,
        Pagination::class,
        QueryTimeOptionModifier::class,
        SortOrderOptionModifier::class,
        SynonymsOptionModifier::class,
    ];
}
