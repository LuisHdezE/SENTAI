<?php

namespace Sentai\Modules\Identity\Domain\Authorization;

final class Capabilities
{
    public const CATALOG_READ = 'catalog.read';
    public const CUSTOMER_ORDER_CREATE = 'customer.order.create';
    public const CUSTOMER_ORDER_READ = 'customer.order.read';
    public const CUSTOMER_FINANCE_READ = 'customer.finance.read';
    public const WAREHOUSE_RECEIVE = 'warehouse.receive';
    public const WAREHOUSE_PUTAWAY = 'warehouse.putaway';
    public const WAREHOUSE_PICKING = 'warehouse.picking';
    public const WAREHOUSE_PACKING = 'warehouse.packing';
    public const INVENTORY_READ = 'inventory.read';
    public const INVENTORY_ADJUST = 'inventory.adjust';
    public const INVENTORY_LOCATION_BLOCK = 'inventory.location.block';
    public const FULFILLMENT_ALLOCATE = 'fulfillment.allocate';
    public const DISPATCH_PLAN = 'dispatch.plan';
    public const DISPATCH_CONFIRM = 'dispatch.confirm';
    public const COMMERCIAL_ORDER_APPROVE = 'commercial.order.approve';
    public const COMMERCIAL_CUSTOMER_MAINTAIN = 'commercial.customer.maintain';
    public const FINANCE_PAYMENT_REGISTER = 'finance.payment.register';
    public const FINANCE_PAYMENT_APPLY = 'finance.payment.apply';
    public const FINANCE_RECEIVABLES_READ = 'finance.receivables.read';
    public const ADMIN_IDENTITY_MANAGE = 'admin.identity.manage';
    public const ADMIN_ROLES_MANAGE = 'admin.roles.manage';
    public const ADMIN_MASTERS_MAINTAIN = 'admin.masters.maintain';
    public const AUDIT_GLOBAL_READ = 'audit.global.read';

    public const ALL = [
        self::CATALOG_READ,
        self::CUSTOMER_ORDER_CREATE,
        self::CUSTOMER_ORDER_READ,
        self::CUSTOMER_FINANCE_READ,
        self::WAREHOUSE_RECEIVE,
        self::WAREHOUSE_PUTAWAY,
        self::WAREHOUSE_PICKING,
        self::WAREHOUSE_PACKING,
        self::INVENTORY_READ,
        self::INVENTORY_ADJUST,
        self::INVENTORY_LOCATION_BLOCK,
        self::FULFILLMENT_ALLOCATE,
        self::DISPATCH_PLAN,
        self::DISPATCH_CONFIRM,
        self::COMMERCIAL_ORDER_APPROVE,
        self::COMMERCIAL_CUSTOMER_MAINTAIN,
        self::FINANCE_PAYMENT_REGISTER,
        self::FINANCE_PAYMENT_APPLY,
        self::FINANCE_RECEIVABLES_READ,
        self::ADMIN_IDENTITY_MANAGE,
        self::ADMIN_ROLES_MANAGE,
        self::ADMIN_MASTERS_MAINTAIN,
        self::AUDIT_GLOBAL_READ,
    ];

    private function __construct()
    {
    }
}
