<?php

namespace Croox\StatamicMeilisearch\Modification\SortOrder;

use Croox\StatamicMeilisearch\Index;
use Croox\StatamicMeilisearch\QueryBuilder;
use Croox\StatamicMeilisearch\Modification\MeilisearchOptionModifier;

class SortOrderOptionModifier extends MeilisearchOptionModifier
{
    /** @var list<string> */
    private array $activeSort = [ ];

    /** @return list<string> */
    public function getActiveSort(): array
    {
        return $this->activeSort;
    }

    /** @param array<string, mixed> $config */
    public function preProcessConfiguration(array $config): array
    {
        $sortConfig = $this->config($config);

        $config['settings']['sortableAttributes'] = $config['settings']['sortableAttributes'] ?? [ ];
        foreach ($sortConfig['available_fields'] as $field) {
            $config['settings']['sortableAttributes'][] = $field;
        }

        if ($sortConfig['ranking_rules'] !== null) {
            $config['settings']['rankingRules'] = $sortConfig['ranking_rules'];
        }

        return $config;
    }

    public function preProcessQueryOptions(Index $index, QueryBuilder $query, array $options): array
    {
        $config = $this->config($index->config());
        if (count($config['available_fields']) === 0) {
            return $options;
        }

        $options['sort'] = $options['sort'] ?? [ ];

        $sortOrder = request()->get('sort_order');
        if (is_array($sortOrder)) {
            foreach ($sortOrder as $order) {
                [ $attribute, $direction ] = explode(':', $order);
                if (!in_array($attribute, $config['available_fields']) || !in_array($direction, ['asc', 'desc'])) {
                    continue;
                }
                $options['sort'][] = $order;
            }
        }
        if (empty($options['sort']) && $config['default_sort']) {
            $options['sort'] = $config['default_sort'];
        }

        $this->activeSort = $options['sort'];
        $options['showRankingScore'] = true;
        $options['showRankingScoreDetails'] = true;

        return $options;
    }

    /**
     * @return array{
     *     available_fields: list<string>,
     *     ranking_rules: list<string>|null,
     *     default_sort: list<string>,
     * }
     *
     * @psalm-suppress InvalidReturnStatement, InvalidReturnType
     */
    private function config(array $fullConfig): array
    {
        $config = $fullConfig['meilisearch']['sort_order'] ?? [ ];
        $config = is_array($config) ? $config : [];

        $config['available_fields'] = $config['available_fields'] ?? [ ];
        $config['ranking_rules'] = $config['ranking_rules'] ?? null;
        $config['default_sort'] = $config['default_sort'] ?? null;

        return $config;
    }
}
