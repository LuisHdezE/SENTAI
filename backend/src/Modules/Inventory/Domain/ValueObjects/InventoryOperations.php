<?php

namespace Sentai\Modules\Inventory\Domain\ValueObjects;

/**
 * Canonical operation names used as idempotency scopes, audit `operation` values and
 * receipt adjustment/movement operations.
 */
final class InventoryOperations
{
    public const RECEIVE_ASN = 'receiveAsn';

    public const CONFIRM_PUT_AWAY = 'confirmPutAway';

    public const ADJUST_INVENTORY = 'adjustInventory';

    public const MOVE_INVENTORY = 'moveInventory';

    public const BLOCK_LOCATION = 'blockLocation';

    public const UNBLOCK_LOCATION = 'unblockLocation';

    private function __construct() {}
}
