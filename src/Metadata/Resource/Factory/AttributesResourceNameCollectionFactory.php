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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;
use ApiPlatform\Util\ReflectionClassRecursiveIterator;

/**
 * Creates a resource name collection from {@see ApiResource} annotations.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class AttributesResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    private $paths;
    private $decorated;

    /**
     * @param string[] $paths
     */
    public function __construct(array $paths, ResourceNameCollectionFactoryInterface $decorated = null)
    {
        $this->paths = $paths;
        $this->decorated = $decorated;
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
            if (\PHP_VERSION_ID >= 80000 && $this->isResource($reflectionClass)) {
                $classes[$className] = true;
            }
        }

        return new ResourceNameCollection(array_keys($classes));
    }

    private function isResource(\ReflectionClass $reflectionClass): bool
    {
        if ($reflectionClass->getAttributes(ApiResource::class)) {
            return true;
        }

        if ($reflectionClass->getAttributes(HttpOperation::class, \ReflectionAttribute::IS_INSTANCEOF) || $reflectionClass->getAttributes(GraphQlOperation::class, \ReflectionAttribute::IS_INSTANCEOF)) {
            return true;
        }

        return false;
    }
}
