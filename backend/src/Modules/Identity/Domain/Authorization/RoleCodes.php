<?php

namespace Sentai\Modules\Identity\Domain\Authorization;

final class RoleCodes
{
    public const CUSTOMER = 'customer';

    public const WAREHOUSE_OPERATOR = 'warehouse_operator';

    public const WAREHOUSE_SUPERVISOR = 'warehouse_supervisor';

    public const DISPATCH_PLANNER = 'dispatch_planner';

    public const COMMERCIAL = 'commercial';

    public const FINANCE = 'finance';

    public const ADMINISTRATOR = 'administrator';

    public const AUDIT_VIEWER = 'audit_viewer';

    public const ALL = [
        self::CUSTOMER,
        self::WAREHOUSE_OPERATOR,
        self::WAREHOUSE_SUPERVISOR,
        self::DISPATCH_PLANNER,
        self::COMMERCIAL,
        self::FINANCE,
        self::ADMINISTRATOR,
        self::AUDIT_VIEWER,
    ];

    private function __construct() {}
}
