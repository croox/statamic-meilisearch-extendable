<?php

namespace Croox\StatamicMeilisearchExtendable\Modification;

use Croox\StatamicMeilisearchExtendable\Meilisearch\Index;
use Croox\StatamicMeilisearchExtendable\Meilisearch\Query;
use Croox\StatamicMeilisearchExtendable\Snippets\SnippetExtractor;
use Illuminate\Support\Collection;
use Meilisearch\Search\SearchResult;

class SearchSnippets extends MeilisearchOptionModifier
{
    protected const DEFAULT_SNIPPET_LENGTH = 100;

    public function preProcessQueryOptions(Index $index, Query $query, array $options): array
    {
        $options = array_replace($index->config()['query_options'] ?? [], $options);

        if ($this->getSnippetLength($index, $options) !== null) {
            $options['attributesToHighlight'] = $options['attributesToHighlight'] ?? [ '*' ];
            $options['highlightPreTag'] = $options['highlightPreTag'] ?? '<mark>';
            $options['highlightPostTag'] = $options['highlightPostTag'] ?? '</mark>';
        }

        return $options;
    }

    public function postProcessResults(Index $index, SearchResult $results, array $queryOptions): SearchResult
    {
        $snippetLength = $this->getSnippetLength($index, $queryOptions);
        if ($snippetLength === null) {
            return $results;
        }

        $extractor = new SnippetExtractor(
            $snippetLength,
            $queryOptions['highlightPreTag'],
            $queryOptions['highlightPostTag']
        );

        return $results->transformHits(function (array $hits) use ($extractor) {
            $transformed = [ ];
            foreach ($hits as $hit) {
                $hit['search_snippets'] = $extractor->extractSearchSnippetsFromMeilisearchResult(
                    $hit['_formatted'] ?? [ ],
                );
                $transformed[] = $hit;
            }

            return $transformed;
        });
    }

    public function extractExtraResultDataFromRawResult(array $rawResult): array
    {
        return [
            'search_snippets' => $rawResult['search_snippets'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $queryOptions
     * @return int<1, max>
     */
    private function getSnippetLength(Index $index, array $queryOptions): ?int
    {
        $config = $index->config();
        if (
            empty($config['snippet_length'])
            && empty($config['meilisearch']['snippet_length'])
            && empty($queryOptions['attributesToHighlight'])
        ) {
            return null;
        }

        $length = $config['snippet_length'] ?? $config['meilisearch']['snippet_length'] ?? self::DEFAULT_SNIPPET_LENGTH;
        if ($length < 1) {
            return null;
        }

        return $length;
    }
}
