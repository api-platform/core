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

namespace ApiPlatform\Core\Util;

use ApiPlatform\Core\Annotation\ApiFilter;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Inflector\Inflector;

/**
 * Generates a service id for a generic filter.
 *
 * @internal
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
trait AnnotationFilterExtractorTrait
{
    /**
     * Filters annotations to get back only ApiFilter annotations.
     *
     * @param array $miscAnnotations class or property annotations
     *
     * @return array only ApiFilter annotations
     */
    private function getFilterAnnotations(array $miscAnnotations): \Iterator
    {
        foreach ($miscAnnotations as $miscAnnotation) {
            if (ApiFilter::class === get_class($miscAnnotation)) {
                yield $miscAnnotation;
            }
        }
    }

    /**
     * Given a filter annotation and reflection elements, find out the properties where the filter is applied.
     */
    private function getFilterProperties(ApiFilter $filterAnnotation, \ReflectionClass $reflectionClass, \ReflectionProperty $reflectionProperty = null): array
    {
        $properties = [];

        if ($filterAnnotation->properties) {
            foreach ($filterAnnotation->properties as $property => $strategy) {
                $properties[$property] = $strategy;
            }

            return $properties;
        }

        if ($filterAnnotation->strategy) {
            if (null !== $reflectionProperty) {
                $properties[$reflectionProperty->getName()] = $filterAnnotation->strategy;

                return $properties;
            }

            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $properties[$reflectionProperty->getName()] = $filterAnnotation->strategy;
            }
        }

        return $properties;
    }

    /**
     * Reads filter annotations from a ReflectionClass.
     *
     * @return array Key is the filter id. It has two values, properties and the ApiFilter instance
     */
    private function readFilterAnnotations(\ReflectionClass $reflectionClass, Reader $reader): array
    {
        $filters = [];

        foreach ($this->getFilterAnnotations($reader->getClassAnnotations($reflectionClass)) as $filterAnnotation) {
            $filterClass = $filterAnnotation->filterClass;
            $id = $this->generateFilterId($reflectionClass, $filterClass);

            if (!isset($filters[$id])) {
                $filters[$id] = [$filterAnnotation->arguments, $filterClass];
            }

            if ($properties = $this->getFilterProperties($filterAnnotation, $reflectionClass)) {
                $filters[$id][0]['properties'] = $properties;
            }
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            foreach ($this->getFilterAnnotations($reader->getPropertyAnnotations($reflectionProperty)) as $filterAnnotation) {
                $filterClass = $filterAnnotation->filterClass;
                $id = $this->generateFilterId($reflectionClass, $filterClass);

                if (!isset($filters[$id])) {
                    $filters[$id] = [$filterAnnotation->arguments, $filterClass];
                }

                if ($properties = $this->getFilterProperties($filterAnnotation, $reflectionClass, $reflectionProperty)) {
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
            return array_merge($filters, $this->readFilterAnnotations($parent, $reader));
        }

        return $filters;
    }

    /**
     * Generates a unique, per-class and per-filter identifier prefixed by `annotated_`.
     *
     * @param \ReflectionClass $reflectionClass the reflection class of a Resource
     * @param string           $filterClass     the filter class
     *
     * @return string
     */
    private function generateFilterId(\ReflectionClass $reflectionClass, string $filterClass): string
    {
        return 'annotated_'.Inflector::tableize(str_replace('\\', '', $reflectionClass->getName().(new \ReflectionClass($filterClass))->getName()));
    }
}
