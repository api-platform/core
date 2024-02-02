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

namespace ApiPlatform\Metadata\Util;

use ApiPlatform\Metadata\ApiFilter;

/**
 * Generates a service id for a generic filter.
 *
 * @internal
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
trait AttributeFilterExtractorTrait
{
    /**
     * Filters annotations to get back only ApiFilter annotations.
     *
     * @return \Iterator only ApiFilter annotations
     */
    private function getFilterAttributes(\ReflectionClass|\ReflectionProperty $reflector): \Iterator
    {
        $attributes = $reflector->getAttributes(ApiFilter::class);

        foreach ($attributes as $attribute) {
            yield $attribute->newInstance();
        }
    }

    /**
     * Given a filter attribute and reflection elements, find out the properties where the filter is applied.
     */
    private function getFilterProperties(ApiFilter $filterAttribute, \ReflectionClass $reflectionClass, ?\ReflectionProperty $reflectionProperty = null): array
    {
        $properties = [];

        if ($filterAttribute->properties) {
            foreach ($filterAttribute->properties as $property => $strategy) {
                if (\is_int($property)) {
                    $properties[$strategy] = null;
                } else {
                    $properties[$property] = $strategy;
                }
            }

            return $properties;
        }

        if (null !== $reflectionProperty) {
            $properties[$reflectionProperty->getName()] = $filterAttribute->strategy ?: null;

            return $properties;
        }

        if ($filterAttribute->strategy) {
            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $properties[$reflectionProperty->getName()] = $filterAttribute->strategy;
            }
        }

        return $properties;
    }

    /**
     * Reads filter attribute from a ReflectionClass.
     *
     * @return array Key is the filter id. It has two values, properties and the ApiFilter instance
     */
    private function readFilterAttributes(\ReflectionClass $reflectionClass): array
    {
        $filters = [];

        foreach ($this->getFilterAttributes($reflectionClass) as $filterAttribute) {
            $filterClass = $filterAttribute->filterClass;
            $id = $this->generateFilterId($reflectionClass, $filterClass, $filterAttribute->id);

            if (!isset($filters[$id])) {
                $filters[$id] = [$filterAttribute->arguments, $filterClass];
            }

            if ($properties = $this->getFilterProperties($filterAttribute, $reflectionClass)) {
                $filters[$id][0]['properties'] = $properties;
            }
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            foreach ($this->getFilterAttributes($reflectionProperty) as $filterAttribute) {
                $filterClass = $filterAttribute->filterClass;
                $id = $this->generateFilterId($reflectionClass, $filterClass, $filterAttribute->id);

                if (!isset($filters[$id])) {
                    $filters[$id] = [$filterAttribute->arguments, $filterClass];
                }

                if ($properties = $this->getFilterProperties($filterAttribute, $reflectionClass, $reflectionProperty)) {
                    if (isset($filters[$id][0]['properties'])) {
                        $filters[$id][0]['properties'] = array_merge($filters[$id][0]['properties'], $properties);
                    } else {
                        $filters[$id][0]['properties'] = $properties;
                    }
                }
            }
        }

        $parent = $reflectionClass->getParentClass();

        if (false !== $parent) {
            return array_merge($filters, $this->readFilterAttributes($parent));
        }

        return $filters;
    }

    /**
     * Generates a unique, per-class and per-filter identifier prefixed by `annotated_`.
     *
     * @param \ReflectionClass $reflectionClass the reflection class of a Resource
     * @param string           $filterClass     the filter class
     * @param string|null      $filterId        the filter id
     */
    private function generateFilterId(\ReflectionClass $reflectionClass, string $filterClass, ?string $filterId = null): string
    {
        $suffix = null !== $filterId ? '_'.$filterId : $filterId;

        return 'annotated_'.Inflector::tableize(str_replace('\\', '', $reflectionClass->getName().(new \ReflectionClass($filterClass))->getName().$suffix));
    }
}
