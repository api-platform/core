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
use ApiPlatform\Metadata\ParameterProviderFilterInterface;
use ApiPlatform\State\ParameterProvider\IriConverterParameterProvider;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\MappingException;

/**
 * @author Vincent Amstoutz <vincent.amstoutz.dev@gmail.com>
 */
final class IriFilter implements FilterInterface, OpenApiParameterFilterInterface, ParameterProviderFilterInterface, ManagerRegistryAwareInterface
{
    use BackwardCompatibleFilterDescriptionTrait;
    use ManagerRegistryAwareTrait;
    use NestedPropertyHelperTrait;
    use OpenApiFilterTrait;

    /**
     * @throws MappingException
     */
    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array &$context = []): void
    {
        $parameter = $context['parameter'];

        if (null === $parameter->getProperty()) {
            throw new InvalidArgumentException(\sprintf('The filter parameter with key "%s" must specify a property. Please provide the property explicitly.', $parameter->getKey()));
        }

        $value = $parameter->getValue();
        $operator = $context['operator'] ?? 'addAnd';
        $match = $context['match'] = $context['match'] ??
            $aggregationBuilder
            ->matchExpr();

        $documentManager = $this->getManagerRegistry()->getManagerForClass($resourceClass);
        if (!$documentManager instanceof DocumentManager) {
            return;
        }

        $property = $parameter->getProperty();
        $matchField = $this->addNestedParameterLookups($property, $aggregationBuilder, $parameter, false, $context);

        $nestedPropertiesInfo = $parameter->getExtraProperties()['nested_properties_info'] ?? [];
        $nestedInfo = $nestedPropertiesInfo ? reset($nestedPropertiesInfo) : null;
        $leafClass = $nestedInfo['leaf_class'] ?? $resourceClass;
        $leafProperty = $nestedInfo['leaf_property'] ?? $property;
        $classMetadata = $documentManager->getClassMetadata($leafClass);

        if (!$classMetadata->hasReference($leafProperty)) {
            return;
        }

        $method = $classMetadata->isSingleValuedAssociation($leafProperty) ? 'references' : 'includesReferenceTo';

        if (is_iterable($value)) {
            $or = $aggregationBuilder->matchExpr();

            foreach ($value as $v) {
                if (!\is_object($v)) {
                    continue;
                }

                $or->addOr($aggregationBuilder->matchExpr()->field($matchField)->{$method}($v));
            }

            $match->{$operator}($or);

            return;
        }

        // The IRI did not resolve to a resource: emit an always-false clause so the query
        // returns no result rather than attempting to match against a raw IRI string.
        if (!\is_object($value)) {
            $match->{$operator}(
                $aggregationBuilder->matchExpr()->field($matchField)->in([])
            );

            return;
        }

        $match
            ->{$operator}(
                $aggregationBuilder
                    ->matchExpr()
                    ->field($matchField)
                    ->{$method}($value)
            );
    }

    public static function getParameterProvider(): string
    {
        return IriConverterParameterProvider::class;
    }
}
