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

namespace ApiPlatform\Serializer;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\PropertyInfo\Type;

/**
 * Interface for collecting cache tags during normalization.
 *
 * @author Urban Suppiger <urban@suppiger.net>
 */
interface TagCollectorInterface
{
    public function collect(mixed $object = null, string $format = null, array $context = [], string $iri = null, mixed $data = null, string $attribute = null, ApiProperty $propertyMetadata = null, Type $type = null): void;
}
