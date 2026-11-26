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

namespace ApiPlatform\Tests\Fixtures\TestBundle\RuntimeResource;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;

final class RuntimeResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceNameCollectionFactoryInterface $decorated,
        private readonly RuntimeResources $resources,
    ) {
    }

    public function create(): ResourceNameCollection
    {
        return new ResourceNameCollection([...iterator_to_array($this->decorated->create()), ...array_values($this->resources->classes())]);
    }
}
