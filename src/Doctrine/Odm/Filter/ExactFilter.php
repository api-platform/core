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

namespace ApiPlatform\Doctrine\Odm\Filter;

use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareTrait;
use ApiPlatform\Doctrine\Common\Filter\OpenApiFilterTrait;
use ApiPlatform\Doctrine\Odm\NestedPropertyHelperTrait;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class ExactFilter implements FilterInterface, OpenApiParameterFilterInterface, ManagerRegistryAwareInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use ManagerRegistryAwareTrait;
    use NestedPropertyHelperTrait;
    use OpenApiFilterTrait;

    /**
     * @throws MappingException
     * @throws LockException
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $parameter = $context['parameter'];

        if (null === $parameter->getProperty()) {
            throw new InvalidArgumentException(\sprintf('The filter parameter with key "%s" must specify a property. Please provide the property explicitly.', $parameter->getKey()));
        }

        $property = $parameter->getProperty();
        $value = $parameter->getValue();
        $operator = $context['operator'] ?? 'addAnd';
        $match = $context['match'] = $context['match'] ??
            $aggregationBuilder
            ->matchExpr();

        $documentManager = $this->getManagerRegistry()->getManagerForClass($resourceClass);
        if (!$documentManager instanceof DocumentManager) {
            return;
        }

        $matchField = $this->addNestedParameterLookups($property, $aggregationBuilder, $parameter);

        $nestedInfo = $parameter->getExtraProperties()['nested_property_info'] ?? null;
        $leafClass = $nestedInfo['leaf_class'] ?? $resourceClass;
        $leafProperty = $nestedInfo['leaf_property'] ?? $property;
        $classMetadata = $documentManager->getClassMetadata($leafClass);

        if (!$classMetadata->hasReference($leafProperty)) {
            $match
                ->{$operator}($aggregationBuilder->matchExpr()->field($matchField)->{is_iterable($value) ? 'in' : 'equals'}($value));

            return;
        }

        $mapping = $classMetadata->getFieldMapping($leafProperty);
        $method = $classMetadata->isSingleValuedAssociation($leafProperty) ? 'references' : 'includesReferenceTo';

        if (is_iterable($value)) {
            $or = $aggregationBuilder->matchExpr();

            foreach ($value as $v) {
                $or->addOr($aggregationBuilder->matchExpr()->field($matchField)->{$method}($documentManager->getPartialReference($mapping['targetDocument'], $v)));
            }

            $match->{$operator}($or);

            return;
        }

        $match
            ->{$operator}(
                $aggregationBuilder->matchExpr()
                    ->field($matchField)
                    ->{$method}($documentManager->getPartialReference($mapping['targetDocument'], $value))
            );
    }
}
