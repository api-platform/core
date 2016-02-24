<?php

/*
 * This file is part of the API Platform project.
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
        if (!isset($context['json_ld_has_context'])) {
            $context += [
                'resource' => $resource,
                'json_ld_has_context' => true,
                // Don't use hydra:Collection in sub levels
                'json_ld_sub_level' => true,
                'json_ld_normalization_groups' => $resource->getNormalizationGroups(),
                'json_ld_denormalization_groups' => $resource->getDenormalizationGroups(),
                'json_ld_validation_groups' => $resource->getValidationGroups(),
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
