<?php

namespace App\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Sentai\Modules\Identity\Domain\Authorization\Capabilities;
use Sentai\Modules\Identity\Domain\Authorization\RoleCodes;

final class CanonicalAuthorizationSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    private const ROLE_CAPABILITIES = [
        RoleCodes::CUSTOMER => [Capabilities::CATALOG_READ, Capabilities::CUSTOMER_ORDER_CREATE, Capabilities::CUSTOMER_ORDER_READ, Capabilities::CUSTOMER_FINANCE_READ],
        RoleCodes::WAREHOUSE_OPERATOR => [Capabilities::WAREHOUSE_RECEIVE, Capabilities::WAREHOUSE_PUTAWAY, Capabilities::WAREHOUSE_PICKING, Capabilities::WAREHOUSE_PACKING],
        RoleCodes::WAREHOUSE_SUPERVISOR => [Capabilities::INVENTORY_READ, Capabilities::INVENTORY_ADJUST, Capabilities::INVENTORY_LOCATION_BLOCK, Capabilities::FULFILLMENT_ALLOCATE],
        RoleCodes::DISPATCH_PLANNER => [Capabilities::DISPATCH_PLAN, Capabilities::DISPATCH_CONFIRM],
        RoleCodes::COMMERCIAL => [Capabilities::COMMERCIAL_ORDER_APPROVE, Capabilities::COMMERCIAL_CUSTOMER_MAINTAIN],
        RoleCodes::FINANCE => [Capabilities::FINANCE_PAYMENT_REGISTER, Capabilities::FINANCE_PAYMENT_APPLY, Capabilities::FINANCE_RECEIVABLES_READ],
        RoleCodes::ADMINISTRATOR => [Capabilities::ADMIN_IDENTITY_MANAGE, Capabilities::ADMIN_ROLES_MANAGE, Capabilities::ADMIN_MASTERS_MAINTAIN],
        RoleCodes::AUDIT_VIEWER => [Capabilities::AUDIT_GLOBAL_READ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();

            foreach (Capabilities::ALL as $capability) {
                if (DB::table('permissions')->where('code', $capability)->value('id') === null) {
                    DB::table('permissions')->insert(['id' => (string) Str::ulid(), 'code' => $capability, 'name' => $capability, 'created_at' => $now, 'updated_at' => $now]);
                }
            }

            foreach (RoleCodes::ALL as $roleCode) {
                if (DB::table('roles')->where('code', $roleCode)->value('id') === null) {
                    DB::table('roles')->insert(['id' => (string) Str::ulid(), 'code' => $roleCode, 'name' => str_replace('_', ' ', $roleCode), 'created_at' => $now, 'updated_at' => $now]);
                }
            }

            foreach (self::ROLE_CAPABILITIES as $roleCode => $capabilities) {
                $roleId = (string) DB::table('roles')->where('code', $roleCode)->value('id');

                foreach ($capabilities as $capability) {
                    $permissionId = (string) DB::table('permissions')->where('code', $capability)->value('id');
                    DB::table('role_permissions')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
                }
            }
        });
    }
}
