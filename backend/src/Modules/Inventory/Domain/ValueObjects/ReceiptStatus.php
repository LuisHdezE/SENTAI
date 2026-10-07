<?php

namespace Sentai\Modules\Inventory\Domain\ValueObjects;

/**
 * Lifecycle of a receipt committed by API-INV-001.
 */
enum ReceiptStatus: string
{
    case Received = 'received';
    case PutAwayCompleted = 'put_away_completed';
}
