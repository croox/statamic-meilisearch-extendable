<?php

namespace Croox\StatamicMeilisearch\Modification\Facets;

use DateTimeImmutable;
use Nette\Utils\DateTime;

/**
 * @psalm-type FacetDistributionArray = array<string, positive-int>
 * @psalm-type FacetStatsArray = array{ min?: int, max?: int }
 * @psalm-type FacetActiveArray = array{
 *     min: int|null,
 *     max: int|null,
 *     values: list<string>,
 *     date_min: DateTimeImmutable|null,
 *     date_max: DateTimeImmutable|null,
 * }
 *
 * @api
 */
class FacetDto
{
    /** @psalm-var FacetDistributionArray */
    private array $distribution;

    /** @psalm-var FacetStatsArray */
    private array $stats;

    /** @psalm-var FacetActiveArray */
    private array $active;

    /**
     * @param FacetDistributionArray $distribution
     * @param FacetStatsArray $stats
     * @param FacetActiveArray $active
     */
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
        $this->active['date_min'] = $this->active['date_min'] ?? null;
        $this->active['date_max'] = $this->active['date_max'] ?? null;
    }

    /** @psalm-return FacetDistributionArray */
    public function distribution(): array
    {
        return $this->distribution;
    }

    /** @psalm-return FacetStatsArray */
    public function stats(): array
    {
        return $this->stats;
    }

    /** @psalm-return FacetActiveArray */
    public function active(): array
    {
        return $this->active;
    }

    /**
     * @return array{
     *     min: DateTimeImmutable|null,
     *     max: DateTimeImmutable|null,
     * }
     */
    public function dateStats(): array
    {
        $min = (isset($this->stats['min']) && $this->stats['min'])
            ? DateTimeImmutable::createFromFormat('U', (string) $this->stats['min'])
            : null;

        $max = (isset($this->stats['max']) && $this->stats['max'])
            ? DateTimeImmutable::createFromFormat('U', (string) $this->stats['max'])
            : null;

        // $min and $max could be false
        return [
            'min' => $min ?: null,
            'max' => $max ?: null,
        ];
    }
}
