<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Mock;

use Statamic\Contracts\Search\Result;
use Statamic\Contracts\Search\Searchable;

class MockSearchable implements Searchable
{

    public function __construct(
        public string $reference,
        public array $fields,
    )
    {

    }

    public function getSearchValue(string $field)
    {
        return $this->fields[$field] ?? null;
    }

    public function getSearchReference(): string
    {
        return $this->reference;
    }

    public function toSearchResult(): Result
    {
        return new MockResult($this->fields);
    }
}
