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

namespace ApiPlatform\Symfony\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;

/**
 * Adds the resource classes declared by controller methods carrying an API operation.
 *
 * @internal
 */
final class ControllerApiOperationResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    /**
     * @param array<class-string, true> $controllerOperationResources
     */
    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $decorated,
        private readonly array $controllerOperationResources,
    ) {
    }

    public function create(): ResourceNameCollection
    {
        $classes = [];
        foreach ($this->decorated->create() as $resourceClass) {
            $classes[$resourceClass] = true;
        }

        foreach ($this->controllerOperationResources as $resourceClass => $_) {
            $classes[$resourceClass] = true;
        }

        return new ResourceNameCollection(array_keys($classes));
    }
}
