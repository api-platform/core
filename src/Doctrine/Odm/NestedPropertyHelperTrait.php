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

namespace ApiPlatform\Doctrine\Odm;

use ApiPlatform\Metadata\Parameter;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * Helper trait for handling nested properties in parameter-based filters.
 *
 * Builds $lookup/$unwind pipeline stages from precomputed ODM mapping data
 * (odm_segments) stored in parameter extra properties at metadata-time.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
trait NestedPropertyHelperTrait
{
    /**
     * Adds the necessary lookups for a nested property using precomputed parameter metadata.
     *
     * @param array<string, mixed> $context Shared context for lookup deduplication across filters within the same request
     *
     * @return string The aliased field name to use in match/sort expressions
     */
    protected function addNestedParameterLookups(string $property, Builder $aggregationBuilder, Parameter $parameter, bool $preserveNullAndEmptyArrays = false, array &$context = []): string
    {
        $extraProperties = $parameter->getExtraProperties();
        $nestedInfo = $extraProperties['nested_property_info'] ?? null;

        if (!$nestedInfo) {
            return $property;
        }

        $odmSegments = $nestedInfo['odm_segments'] ?? [];
        $relationSegments = $nestedInfo['relation_segments'] ?? [];
        $leafProperty = $nestedInfo['leaf_property'] ?? $property;

        if (!$odmSegments || !$relationSegments) {
            return $property;
        }

        $alias = '';

        foreach ($odmSegments as $i => $segment) {
            $association = $relationSegments[$i] ?? null;
            if (!$association) {
                break;
            }

            if ('reference' === $segment['type']) {
                $propertyAlias = "{$association}_lkup";
                $localField = "$alias$association";
                $alias .= $propertyAlias;

                $isOwningSide = $segment['is_owning_side'];
                $targetDocument = $segment['target_document'];
                $mappedBy = $segment['mapped_by'] ?? null;

                // Deduplication: skip $lookup/$unwind if already added for this alias
                if (!isset($context['_odm_lookups'][$alias])) {
                    $aggregationBuilder->lookup($targetDocument)
                        ->localField($isOwningSide ? $localField : '_id')
                        ->foreignField($isOwningSide ? '_id' : $mappedBy)
                        ->alias($alias);
                    $aggregationBuilder->unwind("\$$alias")
                        ->preserveNullAndEmptyArrays($preserveNullAndEmptyArrays);

                    $context['_odm_lookups'][$alias] = true;
                }

                $alias .= '.';
            } elseif ('embed' === $segment['type']) {
                $alias = "$alias$association.";
            }
        }

        return "$alias$leafProperty";
    }
}
