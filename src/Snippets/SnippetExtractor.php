<?php

declare(strict_types=1);

namespace Croox\StatamicMeilisearchExtendable\Snippets;

use Illuminate\Support\Arr;

class SnippetExtractor
{
    public function __construct(
        /** @var positive-int */
        private int $leeway,
        private string $startTag,
        private string $endTag,
    ) {
    }

    /**
     * @param array<string, mixed> $formatted
     * @return list<string>
     */
    public function extractSearchSnippetsFromMeilisearchResult(array $formatted): array
    {
        unset($formatted['title']);
        unset($formatted['url']);
        $formatted = Arr::flatten($formatted, PHP_INT_MAX);
        $snippets = [];
        foreach ($formatted as $value) {
            if (!is_string($value) || !str_contains($value, $this->startTag)) {
                continue;
            }

            $offset = 0;
            $ranges = [];
            while ($start = strpos($value, $this->startTag, $offset)) {
                $end = strpos($value, $this->endTag, $start);
                if ($end === false) {
                    break;
                }
                $ranges[] = [
                    max(0, $start - $this->leeway),
                    min(strlen($value), $end + $this->leeway),
                ];
                $offset = $end;
            }

            // Merge overlapping ranges
            foreach ($ranges as $i => $range) {
                $nextRange = $ranges[$i + 1] ?? null;
                if ($nextRange && $nextRange[0] - $range[1] < $this->leeway * 2) {
                    $ranges[$i + 1] = [
                        $range[0],
                        $nextRange[1],
                    ];
                    unset($ranges[$i]);
                }
            }

            foreach ($ranges as $range) {
                $snippets[] = substr($value, $range[0], $range[1] - $range[0]);
            }
        }

        // We don't want URLs
        return array_values(
            array_filter($snippets, function ($snippet) {
                return !filter_var($snippet, FILTER_VALIDATE_URL)
                    && substr_count($snippet, '/') === substr_count($snippet, $this->endTag);
            })
        );
    }
}
