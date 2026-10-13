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

namespace ApiPlatform\JsonApi\Util;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\TypeHelper;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CompositeTypeInterface;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * Decides whether a property is a JSON:API relationship and with which related resource(s).
 *
 * Single source of truth for the attributes/relationships split, shared by the runtime
 * {@see \ApiPlatform\JsonApi\Serializer\ItemNormalizer} and the doc-time
 * {@see \ApiPlatform\JsonApi\JsonSchema\SchemaFactory} so the generated schema cannot drift
 * from the emitted document.
 *
 * @author Antoine Bluchet <antoine@les-tilleuls.coop>
 *
 * @internal
 */
final class ResourceLinkageResolver
{
    public function __construct(private readonly ResourceClassResolverInterface $resourceClassResolver)
    {
    }

    /**
     * Returns the related resource classes a property points to, in declaration order.
     *
     * @return list<array{class-string, bool}> ordered [relatedClass, isCollection] pairs; empty when the property is a plain attribute
     */
    public function getRelationships(ApiProperty $propertyMetadata): array
    {
        $relationships = [];

        if (null === $type = $propertyMetadata->getNativeType()) {
            return $relationships;
        }

        /** @var class-string|null $className */
        $className = null;
        $typeIsResourceClass = function (Type $type) use (&$className): bool {
            return $type instanceof ObjectType && $this->resourceClassResolver->isResourceClass($className = $type->getClassName());
        };

        foreach ($type instanceof CompositeTypeInterface ? $type->getTypes() : [$type] as $t) {
            if (TypeHelper::getCollectionValueType($t)?->isSatisfiedBy($typeIsResourceClass)) {
                $relationships[] = [$className, true];
            } elseif ($t->isSatisfiedBy($typeIsResourceClass)) {
                $relationships[] = [$className, false];
            }
        }

        return $relationships;
    }
}
