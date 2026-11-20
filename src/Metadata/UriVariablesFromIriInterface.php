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

/**
 * Extracts the URI variables of an item from its IRI, without loading the item.
 */
interface UriVariablesFromIriInterface
{
    /**
     * Retrieves the URI variables (identifiers) of the item an IRI references, without loading the item.
     *
     * The returned array is keyed by the operation's URI variable names (for example `['id' => 1]`),
     * which are the same keys the `uri_variables` context of {@see IriConverterInterface::getIriFromResource()} accepts.
     * Values are converted to the identifier's type by the UriVariablesConverter when one is available.
     *
     * @param array<string, mixed> $context
     *
     * @phpstan-param array<string, mixed> $context
     *
     * @psalm-param array{resource_class?: string|class-string, uri_variables?: array<string, string>, ...<string, mixed>} $context
     *
     * @throws InvalidArgumentException when no route matches the IRI, the IRI references a collection, or it does not reference the expected resource
     *
     * @return array<string, mixed>
     */
    public function getUriVariablesFromIri(string $iri, array $context = [], ?Operation $operation = null): array;
}
