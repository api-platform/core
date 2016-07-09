<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\JsonLd\Serializer;

use ApiPlatform\Core\Hypermedia\ContextBuilderInterface;

/**
 * Creates and manipulates the Serializer context.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
trait ContextTrait
{
    /**
     * Import the context defined in metadata and set some default values.
     *
     * @param string $resourceClass
     * @param array  $context
     *
     * @return array
     */
    private function createContext(string $resourceClass, array $context) : array
    {
        if (isset($context['jsonld_has_context'])) {
            return $context;
        }

        return array_merge($context, [
            'jsonld_has_context' => true,
            // Don't use hydra:Collection in sub levels
            'jsonld_sub_level' => true,
            'resource_class' => $resourceClass,
        ]);
    }

    /**
     * Updates the given JSON-LD document to add its @context key.
     *
     * @param ContextBuilderInterface $contextBuilder
     * @param string                  $resourceClass
     * @param array                   $context
     * @param array                   $data
     *
     * @return array
     */
    private function addJsonLdContext(ContextBuilderInterface $contextBuilder, string $resourceClass, array $context, array $data = []) : array
    {
        if (isset($context['jsonld_has_context'])) {
            return $data;
        }

        if (isset($context['jsonld_embed_context'])) {
            $data['@context'] = $contextBuilder->getResourceContext($resourceClass);

            return $data;
        }

        $data['@context'] = $contextBuilder->getResourceContextUri($resourceClass);

        return $data;
    }
}
