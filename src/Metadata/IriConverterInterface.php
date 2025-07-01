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

namespace ApiPlatform\Metadata;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\Exception\OperationNotFoundException;
use ApiPlatform\Metadata\Exception\RuntimeException;

/**
 * Converts item and resources to IRI and vice versa.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface IriConverterInterface
{
    /**
     * Retrieves an item from its IRI.
     *
     * @param array<string, mixed>|array{request?: Request, resource_class?: string|class-string} $context
     *
     * @throws InvalidArgumentException
     * @throws ItemNotFoundException
     */
    public function getResourceFromIri(string $iri, array $context = [], ?Operation $operation = null): object;

    /**
     * Gets the IRI associated with the given item.
     *
     * @param object|class-string                                                                                                                       $resource
     * @param array<string, mixed>|array{force_resource_class?: string|class-string, item_uri_template?: string, uri_variables?: array<string, string>} $context
     *
     * @throws OperationNotFoundException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function getIriFromResource(object|string $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, ?Operation $operation = null, array $context = []): ?string;
}
