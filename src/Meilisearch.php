<?php

namespace Croox\StatamicMeilisearch;

use Croox\StatamicMeilisearch\Modification\AdditionalQueryOptions;
use Croox\StatamicMeilisearch\Modification\EnsureKeyIsNotMaster;
use Croox\StatamicMeilisearch\Modification\Facets\FacetsOptionModifier;
use Croox\StatamicMeilisearch\Modification\Filtering;
use Croox\StatamicMeilisearch\Modification\IndexNamePrefix;
use Croox\StatamicMeilisearch\Modification\MeilisearchOptionModifier;
use Croox\StatamicMeilisearch\Modification\MeilisearchPagination;
use Croox\StatamicMeilisearch\Modification\QueryTime\QueryTimeOptionModifier;
use Croox\StatamicMeilisearch\Modification\RawResults;
use Croox\StatamicMeilisearch\Modification\SearchSnippets;
use Croox\StatamicMeilisearch\Modification\Pagination;
use Croox\StatamicMeilisearch\Modification\SortOrder\SortOrderOptionModifier;

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
    ];
}
