<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Modification;

use Croox\StatamicMeilisearchExtendable\Modification\SynonymsOptionModifier;

class SynonymsOptionModifierTest extends OptionModifierTestCase
{
    public function testAllowsSpecifyingSynonymsInMeilisearchFormat(): void
    {
        $index = $this->createIndex(new SynonymsOptionModifier(), [
            'meilisearch' => [
                'synonyms' => [
                    'Bed' => [ 'Matrace' ],
                    'Matrace' => [ 'Bed' ],
                ],
            ],
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('updateSettings')
            ->with([
                'synonyms' => [
                    'Bed' => [ 'Matrace' ],
                    'Matrace' => [ 'Bed' ],
                ],
            ]);

        $index->update();
    }

    public function testAllowsSpecifyingSynonymsInShorthandFormat(): void
    {
        $index = $this->createIndex(new SynonymsOptionModifier(), [
            'meilisearch' => [
                'synonyms' => [
                    [ 'Matrace', 'Bed', 'Futon' ]
                ],
            ],
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('updateSettings')
            ->with([
                'synonyms' => [
                    'Matrace' => [ 'Bed', 'Futon' ],
                    'Bed' => [ 'Matrace', 'Futon' ],
                    'Futon' => [ 'Matrace', 'Bed' ],
                ],
            ]);

        $index->update();
    }

    public function testMergesShorthandFormats(): void
    {
        $index = $this->createIndex(new SynonymsOptionModifier(), [
            'meilisearch' => [
                'synonyms' => [
                    [ 'Matrace', 'Bed', 'Futon' ],
                    [ 'Bed', 'Sofa' ]
                ],
            ],
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('updateSettings')
            ->with([
                'synonyms' => [
                    'Matrace' => [ 'Bed', 'Futon' ],
                    'Bed' => [ 'Matrace', 'Futon', 'Sofa' ],
                    'Futon' => [ 'Matrace', 'Bed' ],
                    'Sofa' => [ 'Bed' ],
                ],
            ]);

        $index->update();
    }

    public function testMergesShorthandAndMeilisearch(): void
    {
        $index = $this->createIndex(new SynonymsOptionModifier(), [
            'meilisearch' => [
                'synonyms' => [
                    'Bed' => [ 'Sofa' ],
                    [ 'Matrace', 'Bed', 'Futon' ],
                ],
            ],
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('updateSettings')
            ->with([
                'synonyms' => [
                    'Matrace' => [ 'Bed', 'Futon' ],
                    'Bed' => [ 'Sofa', 'Matrace', 'Futon' ],
                    'Futon' => [ 'Matrace', 'Bed' ],
                ],
            ]);

        $index->update();
    }
}
