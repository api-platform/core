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

namespace ApiPlatform\Tests\Fixtures\TestBundle\DynamicResource;

use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceNameCollection;

final class DynamicResourceNameCollectionFactory implements ResourceNameCollectionFactoryInterface
{
    public function __construct(private readonly ResourceNameCollectionFactoryInterface $decorated)
    {
    }

    public function create(): ResourceNameCollection
    {
        $names = iterator_to_array($this->decorated->create());

        if (!\in_array(DynamicResourceHost::class, $names, true)) {
            return new ResourceNameCollection($names);
        }

        return new ResourceNameCollection([...$names, DynamicRecord::class]);
    }
}
