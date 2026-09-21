<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ModuleBoundaryTest extends TestCase
{
    private const ALLOWED_DEPENDENCIES = [
        'Identity' => [],
        'MasterData' => [],
        'Inventory' => ['MasterData'],
        'Orders' => ['MasterData'],
        'Fulfillment' => ['Inventory', 'Orders'],
        'Shipping' => ['Fulfillment'],
        'Finance' => ['Orders', 'MasterData'],
        'Audit' => [],
    ];

    public function test_cross_module_dependencies_use_only_approved_edges_and_contract_seams(): void
    {
        $violations = [];
        $root = dirname(__DIR__, 2).'/src/Modules';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace($root.'/', '', $file->getPathname());
            $sourceModule = explode('/', $relative)[0] ?? null;

            if ($sourceModule === null || ! array_key_exists($sourceModule, self::ALLOWED_DEPENDENCIES)) {
                continue;
            }

            $source = file_get_contents($file->getPathname()) ?: '';
            preg_match_all('/^use\\s+Sentai\\\\Modules\\\\([A-Za-z0-9]+)\\\\([^;]+);/m', $source, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $targetModule = $match[1];
                $targetPath = $match[2];

                if ($targetModule === $sourceModule) {
                    continue;
                }

                if (! in_array($targetModule, self::ALLOWED_DEPENDENCIES[$sourceModule], true)) {
                    $violations[] = "$relative: $sourceModule may not depend on $targetModule";
                    continue;
                }

                $isContract = str_starts_with($targetPath, 'Application\\Contracts\\');
                $isDomainEvent = str_starts_with($targetPath, 'Domain\\Events\\');

                if (! $isContract && ! $isDomainEvent) {
                    $violations[] = "$relative: cross-module dependency on $targetModule must use Application\\Contracts or Domain\\Events ($targetPath)";
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }
}
