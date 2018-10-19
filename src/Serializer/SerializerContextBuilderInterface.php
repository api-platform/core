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

namespace ApiPlatform\Core\Serializer;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds the context used by the Symfony Serializer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface SerializerContextBuilderInterface
{
    /**
     * Creates a serialization context from a Request.
     *
     *
     * @throws RuntimeException
     */
    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null): array;

    /**
     * Gets the effective serializer groups used in normalization/denormalization.
     *
     * Groups are extracted in the following order:
     *
     * - From the "serializer_groups" key of the $options array.
     * - From metadata of the given operation ("collection_operation_name" and "item_operation_name" keys).
     * - From metadata of the current resource.
     *
     *
     * @return (string[]|null)[]
     */
    public function createFromResourceClass(array $options, string $resourceClass): array;

    /**
     * Gets the context for the property name factory.
     */
    public function getPropertyNameCollectionFactoryContext(ResourceMetadata $resourceMetadata): array;
}
