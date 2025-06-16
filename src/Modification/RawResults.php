<?php

namespace Croox\StatamicMeilisearchExtendable\Modification;

class RawResults extends MeilisearchOptionModifier
{
    public function extractExtraResultDataFromRawResult(array $rawResult): array
    {
        return [
            'rawResult' => $rawResult,
        ];
    }
}
