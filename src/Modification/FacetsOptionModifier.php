<?php

namespace Croox\StatamicMeilisearchExtendable\Modification;

use Croox\StatamicMeilisearchExtendable\Meilisearch\Index;
use Croox\StatamicMeilisearchExtendable\Modification\MeilisearchOptionModifier;
use Croox\StatamicMeilisearchExtendable\Meilisearch\Query;
use Illuminate\Http\Request;
use Meilisearch\Search\SearchResult;

class FacetsOptionModifier extends MeilisearchOptionModifier
{
    private ?SearchResult $mostRecentResult = null;

    private array $lastFacetQueryValues = [ ];

    public function __construct(
        private readonly ?Request $request,
    ) {
    }

    public function preProcessConfiguration(array $config): array
    {
        $config['settings'] = $config['settings'] ?? [ ];
        $config['settings']['filterableAttributes'] = array_unique(
            array_merge(
                $config['settings']['filterableAttributes'] ?? [ ],
                $config['meilisearch']['facets'] ?? [ ],
            )
        );

        $config['settings']['faceting'] = $config['settings']['faceting'] ?? [];
        $config['settings']['faceting']['sortFacetValuesBy'] = [
            '*' => 'count'
        ];

        return $config;
    }

    public function preProcessQueryOptions(Index $index, Query $query, array $options): array
    {
        $facets = array_unique(
            array_merge(
                $index->config()['meilisearch']['facets'] ?? [],
                $options['facets'] ?? [],
            )
        );

        if (count($facets) === 0) {
            return $options;
        }

        $options['facets'] = $facets;

        $filter = $options['filter'] ?? [ ];
        if (is_string($filter)) {
            $filter = [ $filter ];
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $queryValues = $this->getFacetQueryValues($options['facets']);
        $this->lastFacetQueryValues = $queryValues;
        foreach ($queryValues as $facet => $values) {
            if (!empty($values['values'])) {
                $filter[] = sprintf('%s IN %s', $facet, json_encode($values['values'], JSON_THROW_ON_ERROR));
            }
            if (isset($values['min'])) {
                $filter[] = sprintf('%s >= %f', $facet, (float) $values['min']);
            }
            if (isset($values['max'])) {
                $filter[] = sprintf('%s <= %f', $facet, (float) $values['max']);
            }
        }

        if (count($filter) > 0) {
            $options['filter'] = $filter;
        }

        return $options;
    }

    /**
     * @param list<string> $facets
     * @return array
     */
    public function getFacetQueryValues(array $facets): array
    {
        $query = $this->request?->query('facet');
        if (!is_array($query)) {
            $query = [ ];
        }

        $queryValues = [ ];

        foreach ($facets as $facet) {
            if (!isset($query[$facet]) || !is_array($query[$facet])) {
                continue;
            }

            if (isset($query[$facet]['values']) && is_array($query[$facet]['values'])) {
                $values = array_values(array_filter(array_map(
                    fn ($value) => str_replace(['"', "'", '[', ']', 'AND', 'OR', '(', ')'], '', (string) $value),
                    $query[$facet]['values']
                )));

                if (!empty($values)) {
                    $queryValues[$facet] = $queryValues[$facet] ?? [ ];
                    $queryValues[$facet]['values'] = $values;
                }
            }

            if (isset($query[$facet]['min']) && is_numeric($query[$facet]['min'])) {
                $queryValues[$facet] = $queryValues[$facet] ?? [ ];
                $queryValues[$facet]['min'] = (float) $query[$facet]['min'];
            }

            if (isset($query[$facet]['max']) && is_numeric($query[$facet]['max'])) {
                $queryValues[$facet] = $queryValues[$facet] ?? [ ];
                $queryValues[$facet]['max'] = (float) $query[$facet]['max'];
            }

            if (isset($query[$facet]['date_min']) && is_string($query[$facet]['date_min'])) {
                $queryValues[$facet] = $queryValues[$facet] ?? [ ];
                $queryValues[$facet]['date_min'] = (new \DateTimeImmutable($query[$facet]['date_min']));
                $queryValues[$facet]['min'] = $queryValues[$facet]['date_min']->getTimestamp();
            }

            if (isset($query[$facet]['date_max']) && is_string($query[$facet]['date_max'])) {
                $queryValues[$facet] = $queryValues[$facet] ?? [ ];
                $queryValues[$facet]['date_max'] = (new \DateTimeImmutable($query[$facet]['date_max']));
                $queryValues[$facet]['max'] = $queryValues[$facet]['date_max']->getTimestamp();
            }
        }

        return $queryValues;
    }

    public function postProcessResults(Index $index, SearchResult $results, array $queryOptions): SearchResult
    {
        $this->mostRecentResult = $results;

        return $results;
    }

    public function getMetadata(): array
    {
        if ($this->mostRecentResult === null) {
            return [];
        }

        $distribution = $this->mostRecentResult->getFacetDistribution();
        $stats = $this->mostRecentResult->getFacetStats();
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
                $this->lastFacetQueryValues[$key] ?? [ ],
            );
        }

        return [
            'facets' => $facets,
        ];
    }
}
