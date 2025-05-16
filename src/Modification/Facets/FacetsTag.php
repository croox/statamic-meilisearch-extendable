<?php

namespace Croox\StatamicMeilisearch\Modification\Facets;

use Statamic\Tags\Tags;

/** @api */
class FacetsTag extends Tags
{
    /**
     * @var string
     * @psalm-suppress NonInvariantDocblockPropertyType
     */
    protected static $handle = 'meilisearch_facets';

    public function __construct(
        private readonly FacetsOptionModifier $facets,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function index()
    {
        $as = $this->params->get('as') ?: 'facets';

        $result = $this->facets->getMostRecentResult();
        if ($result === null) {
            return [ $as => [ ] ];
        }
        $distribution = $result->getFacetDistribution();
        $stats = $result->getFacetStats();
        $query = request()->query('facet');

        $facetKeys = array_unique(array_merge(array_keys($distribution), array_keys($stats)));

        $facets = [ ];
        foreach ($facetKeys as $key) {
            if (empty($distribution[$key]) && empty($stats[$key])) {
                continue;
            }

            /** @psalm-suppress PossiblyInvalidArgument */
            $facets[$key] = new FacetDto(
                $distribution[$key] ?? [ ],
                $stats[$key] ?? [ ],
                $query[$key] ?? [ ],
            );
        }

        return [
            $as => $facets,
        ];
    }
}
