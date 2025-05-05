<?php

namespace Croox\StatamicMeilisearch\Modification\Facets;

use Croox\StatamicMeilisearch\Index;
use Croox\StatamicMeilisearch\Modification\MeilisearchOptionModifier;
use Meilisearch\Search\SearchResult;

class FacetsOptionModifier extends MeilisearchOptionModifier
{
    private ?SearchResult $mostRecentResult = null;

    public function preProcessConfiguration(array $config): array
    {
        $config['settings'] = $config['settings'] ?? [ ];
        $config['settings']['filterableAttributes'] = array_unique(
            array_merge(
                $config['settings']['filterableAttributes'] ?? [ ],
                $config['facets'] ?? [ ],
            )
        );

        return $config;
    }

    public function preProcessQueryOptions(Index $index, string $searchQuery, array $options): array
    {
        $facets = $index->config()['facets'] ?? [ ];
        $options['facets'] = array_unique(array_merge($options['facets'] ?? [ ], $facets));

        $filter = $options['filter'] ?? [ ];
        if (is_string($filter)) {
            $filter = [ $filter ];
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $queryValues = $this->getFacetQueryValues($options['facets']);
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

        $options['filter'] = $filter;

        return $options;
    }

    /**
     * @param list<string> $facets
     * @return array
     */
    public function getFacetQueryValues(array $facets): array
    {
        $query = request()->query('facet');
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

    public function getMostRecentResult(): ?SearchResult
    {
        return $this->mostRecentResult;
    }
}
