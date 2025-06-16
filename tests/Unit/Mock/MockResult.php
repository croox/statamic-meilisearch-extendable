<?php

namespace Croox\StatamicMeilisearchExtendable\Tests\Unit\Mock;

use Statamic\Contracts\Data\Augmented;
use Statamic\Contracts\Search\Result;
use Statamic\Contracts\Search\Searchable;
use Statamic\Fields\Value;
use Statamic\Search\Index;
use Statamic\Support\Arr;

class MockResult implements Result, Augmented
{

    public function __construct(
        public array $data,
    )
    {

    }

    public function augmented(): Augmented
    {
        return $this;
    }

    public function augmentedValue($key)
    {
        return $this->data[$key] ?? null;
    }

    public function toAugmentedArray($keys = null)
    {
        if ($keys === null) {
            return $this->data;
        }

        return Arr::only($this->data, $keys);
    }

    public function toDeferredAugmentedArray($keys = null)
    {
        return $this->toAugmentedArray($keys);
    }

    public function toDeferredAugmentedArrayUsingFields($keys, $fields)
    {
        // TODO: Implement toDeferredAugmentedArrayUsingFields() method.
    }

    public function toAugmentedCollection($keys = null)
    {
        // TODO: Implement toAugmentedCollection() method.
    }

    public function toShallowAugmentedArray()
    {
        // TODO: Implement toShallowAugmentedArray() method.
    }

    public function toShallowAugmentedCollection()
    {
        // TODO: Implement toShallowAugmentedCollection() method.
    }

    public function toEvaluatedAugmentedArray($keys = null)
    {
        // TODO: Implement toEvaluatedAugmentedArray() method.
    }

    public function getRawResult(): array
    {
        // TODO: Implement getRawResult() method.
    }

    public function setRawResult(array $result): Result
    {
        // TODO: Implement setRawResult() method.
    }

    public function getSearchable(): Searchable
    {
        // TODO: Implement getSearchable() method.
    }

    public function getReference(): string
    {
        // TODO: Implement getReference() method.
    }

    public function setIndex(Index $index): Result
    {
        // TODO: Implement setIndex() method.
    }

    public function getIndex(): Index
    {
        // TODO: Implement getIndex() method.
    }

    public function getScore(): int
    {
        return 0;
    }

    public function setScore(int $score)
    {
        // TODO: Implement setScore() method.
    }

    public function getType(): string
    {
        return '';
    }

    public function setType(string $type): Result
    {
        return $this;
    }

    public function getCpTitle(): string
    {
        return '';
    }

    public function getCpUrl(): string
    {
        return '';
    }

    public function getCpBadge(): string
    {
        return '';
    }

    public function jsonSerialize(): mixed
    {
        return $this->data;
    }

    public function get($key): Value
    {
        return new Value($this->data[$key] ?? null);
    }

    public function all()
    {
        // TODO: Implement all() method.
    }

    public function select($keys = null)
    {
        // TODO: Implement select() method.
    }

    public function withRelations($relations)
    {
        return $this;
    }
}
