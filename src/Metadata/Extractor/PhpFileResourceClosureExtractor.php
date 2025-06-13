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
 * Extracts an array of closure from a list of PHP files.
 *
 * @author Loïc Frémont <lc.fremont@gmail.com>
 */
final class PhpFileResourceClosureExtractor extends AbstractClosureExtractor
{
    /**
     * {@inheritdoc}
     */
    protected function isClosureSupported(\Closure $closure): bool
    {
        $resourceReflection = new \ReflectionFunction($closure);

        if (1 !== $resourceReflection->getNumberOfParameters()) {
            return false;
        }

        $firstParameterType = ($resourceReflection->getParameters()[0] ?? null)?->getType();

        if (!$firstParameterType instanceof \ReflectionNamedType) {
            return false;
        }

        // Check if the closure parameter is an API resource
        return is_a($firstParameterType->getName(), ApiResource::class, true);
    }
}
