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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;

final class DynamicPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(private readonly PropertyMetadataFactoryInterface $decorated)
    {
    }

    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        if (!DynamicResourceDefinitions::has($resourceClass)) {
            return $this->decorated->create($resourceClass, $property, $options);
        }

        return DynamicResourceDefinitions::all()[$resourceClass]['properties'][$property] ?? throw new PropertyNotFoundException(\sprintf('Property "%s" of "%s" not found.', $property, $resourceClass));
    }
}
