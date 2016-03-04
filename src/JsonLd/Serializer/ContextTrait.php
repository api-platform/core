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

use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\JsonLd\ContextBuilderInterface;
use ApiPlatform\Core\Metadata\Resource\ItemMetadata;

/**
 * Creates and manipulates the Serializer context.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
trait ContextTrait
{
    /**
     * Import the context defined in metadata and set some default values.
     *
     * @param string       $resourceClass
     * @param ItemMetadata $itemMetadata
     * @param array        $context
     *
     * @return array
     */
    private function createContext(string $resourceClass, ItemMetadata $itemMetadata, array $context, bool $normalization) : array
    {
        if (isset($context['jsonld_has_context'])) {
            return $context;
        }

        $key = $normalization ? 'normalization_context' : 'denormalization_context';
        $context = array_merge($context, $this->getContextValue($itemMetadata, $context, $key, []));
        $context['resource_class'] = $resourceClass;

        return array_merge($context, [
            'jsonld_has_context' => true,
            // Don't use hydra:Collection in sub levels
            'jsonld_sub_level' => true,
        ]);
    }

    /**
     * Updates the resource class and remove the object_to_populate key.
     *
     * @param string $resourceClass
     * @param array  $context
     *
     * @return array
     */
    private function createRelationContext(string $resourceClass, array $context) : array
    {
        $context['resource_class'] = $resourceClass;
        unset($context['item_operation']);
        unset($context['collection_operation']);

        return $context;
    }

    /**
     * Gets a context value.
     *
     * @param ItemMetadata $resourceItemMetadata
     * @param array        $context
     * @param string       $key
     * @param mixed        $defaultValue
     *
     * @return mixed
     */
    private function getContextValue(ItemMetadata $resourceItemMetadata, array $context, string $key, $defaultValue = null)
    {
        if (isset($context[$key])) {
            return $context[$key];
        }

        if (isset($context['collection_operation_name'])) {
            return $resourceItemMetadata->getCollectionOperationAttribute($context['collection_operation_name'], $key, $defaultValue, true);
        }

        if (isset($context['item_operation_name'])) {
            return $resourceItemMetadata->getItemOperationAttribute($context['item_operation_name'], $key, $defaultValue, true);
        }

        return $resourceItemMetadata->getAttribute($key, $defaultValue);
    }

    /**
     * Gets the resource class to use depending of the current data and context.
     *
     * @param ResourceClassResolverInterface $resourceClassResolver
     * @param mixed                          $data
     * @param array                          $context
     *
     * @return string
     */
    private function getResourceClass(ResourceClassResolverInterface $resourceClassResolver, $data, array $context) : string
    {
        return $resourceClassResolver->getResourceClass($data, $context['resource_class'] ?? null, true);
    }

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
