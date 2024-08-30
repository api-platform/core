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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\IsApiResource;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Metadata\Util\ReflectionClassRecursiveIterator;

/**
 * Creates a resource name collection from {@see IsApiResource} concerns.
 *
 * @author Kévin Dunglas <kevin@dunglas.dev>
 */
final class ConcernsResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    /**
     * @param string[] $paths
     */
    public function __construct(
        private readonly array $paths,
        private readonly ?ResourceNameCollectionFactoryInterface $decorated = null,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function create(): ResourceNameCollection
    {
        $classes = [];

        if ($this->decorated) {
            foreach ($this->decorated->create() as $resourceClass) {
                $classes[$resourceClass] = true;
            }
        }

        foreach (ReflectionClassRecursiveIterator::getReflectionClassesFromDirectories($this->paths) as $className => $reflectionClass) {
            if (
                $reflectionClass->hasMethod('apiResource')
                && ($m = $reflectionClass->getMethod('apiResource'))
                && $m->isPublic()
                && $m->isStatic()
            ) {
                $classes[$className] = true;
            }
        }

        return new ResourceNameCollection(array_keys($classes));
    }
}
