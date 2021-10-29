<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests;

use DirectoryIterator;
use PHPUnit\Framework\TestCase;
use Traversable;

/**
 * @internal
 */
final class ComposerJsonTest extends TestCase
{
    private const SRC_DIR = __DIR__ . '/../src';

    /**
     * @test
     */
    public function packageDependenciesEqualRootDependencies(): void
    {
        $usedDependencies = ['symfony/symfony']; // Some builds add this to composer.json
        $rootDependencies = $this->getComposerDependencies(__DIR__ . '/../composer.json');

        foreach ($this->listSubPackages() as $package) {
            $packageDependencies = $this->getComposerDependencies($package . '/composer.json');
            foreach ($packageDependencies as $dependency => $version) {
                // Skip oauth2-framework/* dependencies
                if (str_starts_with($dependency, 'oauth2-framework/')) {
                    continue;
                }

                $message = sprintf(
                    'Dependency "%s" from package "%s" is not defined in root composer.json',
                    $dependency,
                    $package
                );
                static::assertArrayHasKey($dependency, $rootDependencies, $message);

                $message = sprintf(
                    'Dependency "%s:%s" from package "%s" requires a different version in the root composer.json',
                    $dependency,
                    $version,
                    $package
                );
                static::assertSame($version, $rootDependencies[$dependency], $message);

                $usedDependencies[] = $dependency;
            }
        }

        $unusedDependencies = array_diff(array_keys($rootDependencies), array_unique($usedDependencies));
        $message = sprintf(
            'Dependencies declared in root composer.json, which are not declared in any sub-package: %s',
            implode(', ', $unusedDependencies)
        );
        static::assertCount(0, $unusedDependencies, $message);
    }

    /**
     * @test
     */
    public function rootReplacesSubPackages(): void
    {
        $rootReplaces = $this->getComposerReplaces(__DIR__ . '/../composer.json');
        foreach ($this->listSubPackages() as $package) {
            $packageName = $this->getComposerPackageName($package . '/composer.json');
            $message = sprintf('Root composer.json must replace the sub-packages "%s"', $packageName);
            static::assertArrayHasKey($packageName, $rootReplaces, $message);
        }
    }

    private function listSubPackages(?string $path = self::SRC_DIR): Traversable
    {
        foreach (new DirectoryIterator($path) as $dirInfo) {
            if ($dirInfo->getFilename() === 'OpenIdConnect') {
                continue;
            }
            if ($dirInfo->isDir() && ! $dirInfo->isDot()) {
                if ($dirInfo->getFilename() === 'Component') {
                    yield from $this->listSubPackages($dirInfo->getRealPath());
                    continue;
                }
                yield $dirInfo->getRealPath();
            }
        }
    }

    private function getComposerDependencies(string $composerFilePath): array
    {
        return $this->parseComposerFile($composerFilePath)['require'];
    }

    private function getComposerPackageName(string $composerFilePath): string
    {
        return $this->parseComposerFile($composerFilePath)['name'];
    }

    private function getComposerReplaces(string $composerFilePath): array
    {
        return $this->parseComposerFile($composerFilePath)['replace'];
    }

    private function parseComposerFile(string $composerFilePath): array
    {
        return json_decode(file_get_contents($composerFilePath), true);
    }
}
