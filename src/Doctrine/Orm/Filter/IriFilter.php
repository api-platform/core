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

        // Use precomputed ORM leaf metadata when available (nested properties),
        // otherwise resolve at runtime by walking the association chain.
        $ormLeafMetadata = $this->getOrmLeafMetadata($parameter);

        if (null === $ormLeafMetadata) {
            $ormLeafMetadata = $this->resolveLeafMetadataAtRuntime($queryBuilder, $resourceClass, $parameter->getProperty(), $property);
        }

        // Collection associations (OneToMany/ManyToMany) require a JOIN to compare individual elements.
        if ($ormLeafMetadata['is_collection_valued']) {
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

        // Single-valued associations can be compared directly.
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
        $targetClass = $ormLeafMetadata['association_target_class'];
        $em = $queryBuilder->getEntityManager();
        $targetMetadata = $em->getClassMetadata($targetClass);
        $identifierValues = $targetMetadata->getIdentifierValues($value);
        $queryBuilder->setParameter($parameterName, reset($identifierValues), $ormLeafMetadata['identifier_type']);
    }

    public static function getParameterProvider(): string
    {
        return IriConverterParameterProvider::class;
    }

    /**
     * @return array{is_collection_valued: bool, association_target_class: string, identifier_type: ?string}|null
     */
    private function getOrmLeafMetadata(mixed $parameter): ?array
    {
        $extraProperties = $parameter->getExtraProperties();
        $nestedPropertiesInfo = $extraProperties['nested_properties_info'] ?? null;
        if (!$nestedPropertiesInfo) {
            return null;
        }

        $info = $nestedPropertiesInfo[$parameter->getProperty()] ?? null;

        return $info['orm_leaf_metadata'] ?? null;
    }

    /**
     * Resolves leaf metadata at runtime by walking the association chain.
     * Used as fallback when precomputed orm_leaf_metadata is not available.
     *
     * @return array{is_collection_valued: bool, association_target_class: string, identifier_type: ?string}
     */
    private function resolveLeafMetadataAtRuntime(QueryBuilder $queryBuilder, string $resourceClass, string $originalProperty, string $leafProperty): array
    {
        $em = $queryBuilder->getEntityManager();
        $metadata = $em->getClassMetadata($resourceClass);
        $segments = explode('.', $originalProperty);

        for ($i = 0, $count = \count($segments) - 1; $i < $count; ++$i) {
            $associationMapping = $metadata->getAssociationMapping($segments[$i]);
            $metadata = $em->getClassMetadata($associationMapping['targetEntity']);
        }

        $isCollectionValued = $metadata->isCollectionValuedAssociation($leafProperty);
        $associationMapping = $metadata->getAssociationMapping($leafProperty);
        $targetClass = $associationMapping['targetEntity'];

        $identifierType = null;
        if (!$isCollectionValued) {
            $targetMetadata = $em->getClassMetadata($targetClass);
            $idFieldNames = $targetMetadata->getIdentifierFieldNames();
            $identifierType = $targetMetadata->getTypeOfField($idFieldNames[0]);
        }

        return [
            'is_collection_valued' => $isCollectionValued,
            'association_target_class' => $targetClass,
            'identifier_type' => $identifierType,
        ];
    }
}
