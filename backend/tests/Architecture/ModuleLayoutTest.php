<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ModuleLayoutTest extends TestCase
{
    private const MODULES = [
        'Identity',
        'MasterData',
        'Inventory',
        'Orders',
        'Fulfillment',
        'Shipping',
        'Finance',
        'Audit',
    ];

    private const LAYERS = ['Domain', 'Application', 'Infrastructure', 'Presentation'];

    public static function moduleLayerProvider(): iterable
    {
        foreach (self::MODULES as $module) {
            foreach (self::LAYERS as $layer) {
                yield "$module/$layer" => [$module, $layer];
            }
        }
    }

    #[DataProvider('moduleLayerProvider')]
    public function test_canonical_module_layer_exists(string $module, string $layer): void
    {
        self::assertDirectoryExists(dirname(__DIR__, 2)."/src/Modules/$module/$layer");
    }
}
