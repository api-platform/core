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

namespace ApiPlatform\Metadata\Extractor;

use ApiPlatform\Metadata\ApiResource;

/**
 * Extracts an array of metadata from a list of PHP files.
 *
 * @author Loïc Frémont <lc.fremont@gmail.com>
 */
final class PhpFileResourceExtractor extends AbstractResourceExtractor
{
    use ResourceExtractorTrait;

    /**
     * {@inheritdoc}
     */
    protected function extractPath(string $path): void
    {
        $resource = $this->getPHPFileClosure($path)();

        if (!$resource instanceof ApiResource) {
            return;
        }

        $resourceReflection = new \ReflectionClass($resource);

        foreach ($resourceReflection->getProperties() as $property) {
            $property->setAccessible(true);
            $resolvedValue = $this->resolve($property->getValue($resource));
            $property->setValue($resource, $resolvedValue);
        }

        $this->resources[] = $resource;
    }

    /**
     * Scope isolated include.
     *
     * Prevents access to $this/self from included files.
     */
    private function getPHPFileClosure(string $filePath): \Closure
    {
        return \Closure::bind(function () use ($filePath): mixed {
            return require $filePath;
        }, null, null);
    }
}
