<?php

namespace Croox\StatamicMeilisearchExtendable\Tags;

use Croox\StatamicMeilisearchExtendable\Meilisearch\Index;
use Statamic\Facades\Search;
use Statamic\Tags\Concerns\OutputsItems;
use Statamic\Tags\Tags;

class MeilisearchMetadataTag extends Tags
{
    use OutputsItems;

    /**
     * @var string
     * @psalm-suppress NonInvariantDocblockPropertyType
     */
    protected static $handle = 'meilisearch_metadata';

    public function index(): mixed
    {
        $index = Search::index($this->params->get('index'));
        if (!($index instanceof Index)) {
            throw new \InvalidArgumentException(sprintf(
                'The index "%s" is not a valid Meilisearch index.',
                $this->params->get('index')
            ));
        }

        return $this->output($index->getMetadata());
    }
}
