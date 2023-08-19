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

namespace ApiPlatform\HttpCache;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\PropertyInfo\Type;

/**
 * Interface for collecting cache tags during normalization.
 *
 * @author Urban Suppiger <urban@suppiger.net>
 */
interface TagCollectorInterface
{
    public function collectTagsFromNormalize(mixed $object, string $format = null, array $context = [], string $iri = null): void;

    public function collectTagsFromNormalizeRelation(mixed $object, string $format = null, array $context = [], string $iri = null): void;

    public function collectTagsFromGetAttribute(mixed $object, string $format = null, array $context = [], string $iri = null, string $attribute = null, ApiProperty $propertyMetadata = null, Type $type = null, array $childContext = []): void;
}
