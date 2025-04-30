<?php

namespace Croox\StatamicMeilisearch\Modification;

class RawResults extends MeilisearchOptionModifier
{
    public function extractExtraResultDataFromRawResult(array $rawResult): array
    {
        return [
            'rawResult' => $rawResult,
        ];
    }
}