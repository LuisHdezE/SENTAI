<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class LayerDependencyTest extends TestCase
{
    public function test_clean_architecture_dependency_direction_is_respected(): void
    {
        $violations = [];

        foreach ($this->phpFiles(dirname(__DIR__, 2).'/src') as $file) {
            $source = file_get_contents($file->getPathname()) ?: '';
            $relative = str_replace(dirname(__DIR__, 2).'/', '', $file->getPathname());
            $layer = $this->layerFromPath($relative);

            if ($layer === null) {
                continue;
            }

            if (in_array($layer, ['Domain', 'Application'], true)) {
                foreach (['Illuminate\\', 'Laravel\\', 'Symfony\\Component\\HttpFoundation\\', 'App\\'] as $forbidden) {
                    if (str_contains($source, $forbidden)) {
                        $violations[] = "$relative: $layer must not depend on $forbidden";
                    }
                }
            }

            foreach ($this->imports($source) as $import) {
                if ($layer === 'Domain' && preg_match('/\\\\(Application|Infrastructure|Presentation)\\\\/', $import)) {
                    $violations[] = "$relative: Domain import crosses outward to $import";
                }

                if ($layer === 'Application' && preg_match('/\\\\(Infrastructure|Presentation)\\\\/', $import)) {
                    $violations[] = "$relative: Application import crosses outward to $import";
                }

                if ($layer === 'Presentation' && str_contains($import, '\\Infrastructure\\')) {
                    $violations[] = "$relative: Presentation must not access Infrastructure directly ($import)";
                }

                if ($layer === 'Infrastructure' && str_contains($import, '\\Presentation\\')) {
                    $violations[] = "$relative: Infrastructure must not depend on Presentation ($import)";
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    /** @return list<SplFileInfo> */
    private function phpFiles(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        return $files;
    }

    /** @return list<string> */
    private function imports(string $source): array
    {
        preg_match_all('/^use\\s+([^;]+);/m', $source, $matches);

        return array_values(array_map('trim', $matches[1] ?? []));
    }

    private function layerFromPath(string $path): ?string
    {
        foreach (['Domain', 'Application', 'Infrastructure', 'Presentation'] as $layer) {
            if (str_contains($path, "/$layer/")) {
                return $layer;
            }
        }

        return null;
    }
}
