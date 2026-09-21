<?php

namespace Sentai\Modules\MasterData\Domain\ValueObjects;

enum MasterType: string
{
    case Product = 'product';
    case Customer = 'customer';
    case Warehouse = 'warehouse';
    case Zone = 'zone';
    case Location = 'location';

    public function aggregateName(): string
    {
        return match ($this) {
            self::Product => 'Product',
            self::Customer => 'Customer',
            self::Warehouse => 'Warehouse',
            self::Zone => 'Zone',
            self::Location => 'Location',
        };
    }
}
