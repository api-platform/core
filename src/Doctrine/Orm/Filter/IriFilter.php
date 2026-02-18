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

namespace ApiPlatform\Doctrine\Orm\Filter;

use ApiPlatform\Doctrine\Common\Filter\OpenApiFilterTrait;
use ApiPlatform\Doctrine\Orm\NestedPropertyHelperTrait;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\ParameterProviderFilterInterface;
use ApiPlatform\State\ParameterProvider\IriConverterParameterProvider;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class IriFilter implements FilterInterface, OpenApiParameterFilterInterface, ParameterProviderFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use NestedPropertyHelperTrait;
    use OpenApiFilterTrait;

    public function apply(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $parameter = $context['parameter'];
        $value = $parameter->getValue();

        if (null === $parameter->getProperty()) {
            throw new InvalidArgumentException(\sprintf('The filter parameter with key "%s" must specify a property. Please provide the property explicitly.', $parameter->getKey()));
        }

        $property = $parameter->getProperty();
        $alias = $queryBuilder->getRootAliases()[0];

        [$alias, $property] = $this->addNestedParameterJoins($property, $alias, $queryBuilder, $queryNameGenerator, $parameter);

        $parameterName = $queryNameGenerator->generateParameterName($property);

        // Resolve the metadata for the entity that owns the leaf property.
        // For nested properties like "department.company", we need to walk the association chain
        // to get the metadata of the entity that owns "company" (i.e. FilterDepartment).
        $em = $queryBuilder->getEntityManager();
        $metadata = $em->getClassMetadata($resourceClass);
        $originalProperty = $parameter->getProperty();
        $segments = explode('.', $originalProperty);
        // Walk all segments except the last (which is the leaf property)
        for ($i = 0, $count = \count($segments) - 1; $i < $count; ++$i) {
            $associationMapping = $metadata->getAssociationMapping($segments[$i]);
            $metadata = $em->getClassMetadata($associationMapping['targetEntity']);
        }

        // Determine if the association is a collection (OneToMany/ManyToMany) or single-valued (ManyToOne/OneToOne).
        // Collection associations require a JOIN to compare individual elements.
        // Single-valued associations can be compared directly, which avoids issues with custom ID types (e.g. UUID).
        $isCollectionAssociation = $metadata->isCollectionValuedAssociation($property);

        if ($isCollectionAssociation) {
            $queryBuilder->join(\sprintf('%s.%s', $alias, $property), $parameterName);

            if (is_iterable($value)) {
                $queryBuilder
                    ->{$context['whereClause'] ?? 'andWhere'}(\sprintf('%s IN (:%s)', $parameterName, $parameterName));
            } else {
                $queryBuilder
                    ->{$context['whereClause'] ?? 'andWhere'}(\sprintf('%s = :%s', $parameterName, $parameterName));
            }

            $queryBuilder->setParameter($parameterName, $value);

            return;
        }

        $propertyExpr = \sprintf('%s.%s', $alias, $property);

        if (is_iterable($value)) {
            $queryBuilder
                ->{$context['whereClause'] ?? 'andWhere'}(\sprintf('%s IN (:%s)', $propertyExpr, $parameterName));
            $queryBuilder->setParameter($parameterName, $value);

            return;
        }

        $queryBuilder
            ->{$context['whereClause'] ?? 'andWhere'}(\sprintf('%s = :%s', $propertyExpr, $parameterName));

        // Extract the identifier value and its type from the target entity metadata
        // to properly handle custom ID types (e.g. UUID).
        $associationMapping = $metadata->getAssociationMapping($property);
        $targetMetadata = $em->getClassMetadata($associationMapping['targetEntity']);
        $idFieldNames = $targetMetadata->getIdentifierFieldNames();
        $idType = $targetMetadata->getTypeOfField($idFieldNames[0]);
        $identifierValues = $targetMetadata->getIdentifierValues($value);
        $queryBuilder->setParameter($parameterName, reset($identifierValues), $idType);
    }

    public static function getParameterProvider(): string
    {
        return IriConverterParameterProvider::class;
    }
}
