<?php

namespace Croox\StatamicMeilisearch\Modification;

use Croox\StatamicMeilisearch\Index;
use Croox\StatamicMeilisearch\QueryBuilder;

class Filtering extends MeilisearchOptionModifier
{
    public function preProcessConfiguration(array $config): array
    {
        $config['settings'] = $config['settings'] ?? [ ];
        $config['settings']['filterableAttributes'] = array_unique(
            array_merge(
                $config['settings']['filterableAttributes'] ?? [ ],
                $config['meilisearch']['filtering']['attributes'] ?? [ ],
            )
        );

        return $config;
    }

    /**
     * Pre-process the query options before sending them to Meilisearch.
     * This method is called on every search request.
     */
    public function preProcessQueryOptions(Index $index, QueryBuilder $query, array $options): array
    {
        $config = $index->config();

        $type = (string) ($config['meilisearch']['filter']['type'] ?? 'statamic');
        $isCount = (bool) ($options['_is_count'] ?? false);

        if ($type !== 'meilisearch' && $type !== 'split') {
            return $options;
        }

        $filterableAttributes = $index->config()['settings']['filterableAttributes'] ?? [ ];

        $filter = $options['filter'] ?? [ ];
        if (is_string($filter)) {
            $filter = [ $filter ];
        }

        $unhandledWheres = [ ];
        foreach ($query->getWheres() as $where) {
            if (!in_array($where['column'], $filterableAttributes)) {
                $unhandledWheres[] = $where;
                continue;
            }

            $filterString = $this->whereToMeilisearch($where);
            if ($filterString !== null) {
                $filter[] = $filterString;
            } else {
                $unhandledWheres[] = $where;
            }
        }

        if ($type === 'meilisearch' && count($unhandledWheres) > 0) {
            throw new \InvalidArgumentException(sprintf('
                meilisearch.filtering.type = "meilisearch" requires all filters to be filtered by meilisearch.
                Please ensure that all unhandled wheres are in `meilisearch.filtering.attributes`.
                Unhandled wheres: %s
            ', json_encode($unhandledWheres, JSON_THROW_ON_ERROR)));
        }

        if (!$isCount) {
            $query->setWheres($unhandledWheres);
        }

        $options['filter'] = array_unique(array_merge($filter, $options['filter'] ?? [ ]));


        return $options;
    }

    protected function whereToMeilisearch(array $where): ?string
    {
        $method = 'whereToMeilisearch' . $where['type'];
        if (method_exists($this, $method)) {
            return $this->{$method}($where);
        }

        return null;
    }

    /** @api */
    protected function whereToMeilisearchBasic(array $where): ?string
    {
        switch ($where['operator'] ?? null) {
            case '=':
                return sprintf('%s = %s', $where['column'], json_encode($where['value'], JSON_THROW_ON_ERROR));
            case '!=':
                return sprintf('%s != %s', $where['column'], json_encode($where['value'], JSON_THROW_ON_ERROR));
            case '>':
                return sprintf('%s > %s', $where['column'], json_encode($where['value'], JSON_THROW_ON_ERROR));
            case '<':
                return sprintf('%s < %s', $where['column'], json_encode($where['value'], JSON_THROW_ON_ERROR));
            case '>=':
                return sprintf('%s >= %s', $where['column'], json_encode($where['value'], JSON_THROW_ON_ERROR));
            case '<=':
                return sprintf('%s <= %s', $where['column'], json_encode($where['value'], JSON_THROW_ON_ERROR));
            default:
                return null;
        }
    }
}
