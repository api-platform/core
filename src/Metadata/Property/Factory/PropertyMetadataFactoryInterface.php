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

namespace ApiPlatform\Metadata\Property\Factory;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\PropertyNotFoundException;

/**
 * Creates a property metadata value object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface PropertyMetadataFactoryInterface
{
    /**
     * Creates a property metadata.
     *
     * @param array<string, mixed> $options
     * @param class-string|string  $resourceClass
     *
     * @throws PropertyNotFoundException
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty;
}
