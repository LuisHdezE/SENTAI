<?php

namespace Sentai\Modules\MasterData\Application\Contracts;

/**
 * Read seam over structural Location reference data owned by Master Data.
 *
 * Inventory consumes Locations exclusively through this contract so that location
 * blocking (owned by Inventory) is never conflated with structural location data.
 */
interface LocationDirectory
{
    public function find(string $locationId): ?LocationDirectoryEntry;

    /**
     * Acquire a pessimistic lock on a structural Location reference.
     *
     * Consumers may lock structural reference data for invariant-sensitive work,
     * but this contract exposes no Master Data mutation capability.
     */
    public function lock(string $locationId): ?LocationDirectoryEntry;
}
