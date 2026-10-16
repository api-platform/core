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

namespace ApiPlatform\JsonSchema\Generator;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyInitializableExtractorInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;

final class ApiPlatformPropertyInfoExtractor implements PropertyInfoExtractorInterface, PropertyInitializableExtractorInterface
{
    public function __construct(
        private readonly PropertyMetadataAccessor $accessor,
    ) {
    }

    public function getProperties(string $class, array $context = []): array
    {
        return $this->accessor->getNames($class, $context);
    }

    public function getType(string $class, string $property, array $context = []): ?Type
    {
        $metadata = $this->accessor->get($class, $property, $context);

        if (null === $type = $metadata->getNativeType()) {
            return null;
        }

        if ($type instanceof CollectionType && null !== $metadata->getUriTemplate()) {
            $type = $type->getCollectionValueType();
        }

        return $type;
    }

    public function isReadable(string $class, string $property, array $context = []): bool
    {
        return $this->isExposed($this->accessor->get($class, $property, $context));
    }

    public function isWritable(string $class, string $property, array $context = []): bool
    {
        return $this->isExposed($this->accessor->get($class, $property, $context));
    }

    public function isInitializable(string $class, string $property, array $context = []): bool
    {
        return $this->isExposed($this->accessor->get($class, $property, $context));
    }

    public function getShortDescription(string $class, string $property, array $context = []): ?string
    {
        return null;
    }

    public function getLongDescription(string $class, string $property, array $context = []): ?string
    {
        return null;
    }

    private function isExposed(ApiProperty $metadata): bool
    {
        return false !== $metadata->isReadable() || false !== $metadata->isWritable();
    }
}
