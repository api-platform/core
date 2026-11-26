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
     * Returns the name of a class in JSON-LD and Hydra: the short name of its resource, or of the class.
     */
    public function getTypeName(string $class): string;

    /**
     * Returns the JSON-LD types of a class: the types of its resource, or the short name of the class.
     *
     * @return list<string>
     */
    public function getTypes(string $class): array;
}
