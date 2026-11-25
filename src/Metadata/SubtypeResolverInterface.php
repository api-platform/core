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

namespace ApiPlatform\Metadata;

use ApiPlatform\Metadata\Exception\RuntimeException;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorMapping;

/**
 * Resolves the subtypes a resource exposes through serializer discriminator maps (resource polymorphism).
 *
 * Only strict subclasses declared in a map are subtypes.
 */
interface SubtypeResolverInterface
{
    public function getMapping(string $class): ?ClassDiscriminatorMapping;

    /**
     * @return array<string, class-string> the subtypes declared in the map of the class, indexed by type value
     */
    public function getDirectSubtypes(string $class): array;

    /**
     * @return list<class-string> the subtypes declared in the map of the class and, recursively, in the maps of its subtypes
     */
    public function getSubtypes(string $class): array;

    /**
     * Returns the name of a class in JSON-LD and Hydra: the short name of its resource, or of the subtype.
     *
     * @throws RuntimeException when a subtype name is also the name of a resource or of another subtype
     */
    public function getTypeName(string $class): string;

    /**
     * Returns the JSON-LD types of a subtype, its name unless declared with ApiSubtype.
     *
     * @throws RuntimeException when a subtype name is also the name of a resource or of another subtype
     *
     * @return list<string>
     */
    public function getTypes(string $class): array;
}
