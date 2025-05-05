<?php

namespace Croox\StatamicMeilisearch\Modification\Facets;

/** @api */
class FacetDto
{
    private array $distribution;
    private array $stats;
    private array $active;

    public function __construct(
        array $distribution,
        array $stats,
        array $active,
    ) {
        $this->distribution = $distribution;
        $this->stats = $stats;
        $this->active = $active;

        $this->active['values'] = $this->active['values'] ?? [ ];
        $this->active['min'] = $this->active['min'] ?? null;
        $this->active['max'] = $this->active['max'] ?? null;
    }

    public function distribution(): array
    {
        return $this->distribution;
    }

    public function stats(): array
    {
        return $this->stats;
    }

    public function active(): array
    {
        return $this->active;
    }

    public function dateStats(): array
    {
        $stats = $this->stats;
        return [
            'min' => $stats['min'] ? \DateTimeImmutable::createFromFormat('U', (string) $stats['min']) : null,
            'max' => $stats['max'] ? \DateTimeImmutable::createFromFormat('U', (string) $stats['max']) : null,
        ];
    }
}
