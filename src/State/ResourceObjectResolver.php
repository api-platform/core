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

namespace ApiPlatform\State;

use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\ObjectMapper\Metadata\ObjectMapperMetadataFactoryInterface;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;

final class ResourceObjectResolver implements ResourceObjectResolverInterface
{
    use ClassInfoTrait;

    public function __construct(
        private readonly ResourceClassResolverInterface $resourceClassResolver,
        private readonly ?ObjectMapperInterface $objectMapper = null,
        private readonly ?ObjectMapperMetadataFactoryInterface $objectMapperMetadata = null,
    ) {
    }

    public function resolve(object $data): iterable
    {
        if ($this->resourceClassResolver->isResourceClass($class = $this->getObjectClass($data))) {
            return [$data];
        }

        if (!$this->objectMapper) {
            return [];
        }

        if ($this->objectMapperMetadata) {
            $mappings = $this->objectMapperMetadata->create($data);

            if (!$mappings) {
                return [];
            }

            return array_map(
                fn ($mapping) => $this->objectMapper->map($data, $mapping->target),
                $mappings
            );
        }

        $mapAttributes = (new \ReflectionClass($class))->getAttributes(Map::class);

        if (!$mapAttributes) {
            return [];
        }

        return array_map(
            fn ($mapAttribute) => $this->objectMapper->map($data, $mapAttribute->newInstance()->target),
            $mapAttributes
        );
    }
}
