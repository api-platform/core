<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd\Serializer;

use Dunglas\ApiBundle\Api\ResourceInterface;

/**
 * Serializer context creation and manipulation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait ContextTrait
{
    /**
     * Creates normalization context.
     *
     * @param ResourceInterface $resource
     * @param array             $context
     *
     * @return array
     */
    private function createContext(ResourceInterface $resource, $context)
    {
        if (!isset($context['jsonld_has_context'])) {
            $context += [
                'resource' => $resource,
                'jsonld_has_context' => true,
                // Don't use hydra:Collection in sub levels
                'jsonld_sub_level' => true,
                'jsonld_normalization_groups' => $resource->getNormalizationGroups(),
                'jsonld_denormalization_groups' => $resource->getDenormalizationGroups(),
                'jsonld_validation_groups' => $resource->getValidationGroups(),
            ];
        }

        return $context;
    }

    /**
     * Creates relation context.
     *
     * @param ResourceInterface $resource
     * @param array             $context
     *
     * @return array
     */
    private function createRelationContext(ResourceInterface $resource, array $context)
    {
        $context['resource'] = $resource;
        unset($context['object_to_populate']);

        return $context;
    }
}
