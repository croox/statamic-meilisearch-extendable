<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Modification;

use Croox\StatamicMeilisearchExtendable\Modification\SearchSnippets;
use Meilisearch\Search\SearchResult;

class SearchSnippetsTest extends OptionModifierTestCase
{
    private const UNIMPORTANT_FIELDS = [
        'hitsPerPage' => 20,
        'totalHits' => 1,
        'page' => 1,
        'totalPages' => 1,
        'processingTimeMs' => 1,
        'query' => 'test',
    ];
    public function testCreatesSearchSnippet(): void
    {
        $index = $this->createIndex(new SearchSnippets(), [
            'snippet_length' => 10,
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [
                'attributesToHighlight' => [ '*' ],
                'highlightPreTag' => '<mark>',
                'highlightPostTag' => '</mark>',
            ])->willReturn(new SearchResult([
                ...self::UNIMPORTANT_FIELDS,
                'hits' => [
                    [
                        '_formatted' => [
                            'content' => 'There is a text that contains the word <mark>test</mark> in a somewhat larger string. Here is another <mark>test</mark>.',
                            'another_field' => 'Some other content',
                            'third_field' => 'There is <mark>test</mark> in this field as well.',
                        ]
                    ],
                ]
            ]));

        $result = $index->performSearch($index->search('test'), [ ]);
        $snippets = $result->getHits()[0]['search_snippets'] ?? [ ];

        $this->assertEquals(
            ' the word <mark>test</mark> in[...]s another <mark>test</mark>.[...]There is <mark>test</mark> in',
            implode('[...]', $snippets),
        );
    }

    public function testAllowsModificationOfHighlightTag(): void
    {
        $index = $this->createIndex(new SearchSnippets(), [
            'query_options' => [
                'highlightPreTag' => '<strong>',
                'highlightPostTag' => '</strong>',
            ],
            'snippet_length' => 10,
        ]);

        $this->mockMeiliIndex()
            ->expects($this->once())
            ->method('search')
            ->with('test', [
                'attributesToHighlight' => [ '*' ],
                'highlightPreTag' => '<strong>',
                'highlightPostTag' => '</strong>',
            ])->willReturn(new SearchResult([
                ...self::UNIMPORTANT_FIELDS,
                'hits' => [
                    [
                        '_formatted' => [
                            'content' => 'There is a text that contains the word <strong>test</strong> in a somewhat larger string. Here is another <strong>test</strong>.',
                            'another_field' => 'Some other content',
                            'third_field' => 'There is <strong>test</strong> in this field as well.',
                        ]
                    ],
                ]
            ]));

        $result = $index->performSearch($index->search('test'), [ ]);
        $snippets = $result->getHits()[0]['search_snippets'] ?? [ ];

        $this->assertEquals(
            ' the word <strong>test</strong> [...]s another <strong>test</strong>.[...]There is <strong>test</strong> ',
            implode('[...]', $snippets),
        );
    }
}
