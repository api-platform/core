<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Metadata\Util;

/**
 * Gets reflection classes for php files in the given directories.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @internal
 */
final class ReflectionClassRecursiveIterator
{
    /**
     * @var array<string, array<class-string, \ReflectionClass>>
     */
    private static array $localCache;

    private function __construct()
    {
    }

    /**
     * @param string[] $directories
     * @param string   $ignoreRegex Laravel uses (?!.*Test\.php$) to avoid loading pest class tests
     *
     * @return array<class-string, \ReflectionClass>
     */
    public static function getReflectionClassesFromDirectories(array $directories, string $ignoreRegex = ''): array
    {
        $id = hash('xxh3', implode('', $directories));
        if (isset(self::$localCache[$id])) {
            return self::$localCache[$id];
        }

        $includedFiles = [];
        foreach ($directories as $path) {
            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^'.$ignoreRegex.'.+\.php$/i',
                \RecursiveRegexIterator::GET_MATCH
            );

            foreach ($iterator as $file) {
                $sourceFile = $file[0];

                if (!preg_match('(^phar:)i', (string) $sourceFile)) {
                    $sourceFile = realpath($sourceFile);
                }

                try {
                    require_once $sourceFile;
                } catch (\Throwable) {
                    // invalid PHP file (example: missing parent class)
                    continue;
                }

                $includedFiles[$sourceFile] = true;
            }
        }

        $sortedClasses = get_declared_classes();
        sort($sortedClasses);
        $sortedInterfaces = get_declared_interfaces();
        sort($sortedInterfaces);
        $declared = [...$sortedClasses, ...$sortedInterfaces];
        $ret = [];
        foreach ($declared as $className) {
            $reflectionClass = new \ReflectionClass($className);
            $sourceFile = $reflectionClass->getFileName();
            if (isset($includedFiles[$sourceFile])) {
                $ret[$className] = $reflectionClass;
            }
        }

        return self::$localCache[$id] = $ret;
    }
}
