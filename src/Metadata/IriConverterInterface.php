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
use ApiPlatform\Metadata\Exception\RuntimeException;

if (interface_exists(\ApiPlatform\Api\IriConverterInterface::class)) {
    class_alias(
        \ApiPlatform\Api\IriConverterInterface::class,
        __NAMESPACE__.'\IriConverterInterface'
    );

    if (false) { // @phpstan-ignore-line
        interface IriConverterInterface extends \ApiPlatform\Api\IriConverterInterface
        {
        }
    }
} else {
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
         * @throws InvalidArgumentException
         * @throws ItemNotFoundException
         */
        public function getResourceFromIri(string $iri, array $context = [], ?Operation $operation = null): object;

        /**
         * Gets the IRI associated with the given item.
         *
         * @param object|class-string $resource
         *
         * @throws InvalidArgumentException
         * @throws RuntimeException
         */
        public function getIriFromResource(object|string $resource, int $referenceType = UrlGeneratorInterface::ABS_PATH, ?Operation $operation = null, array $context = []): ?string;
    }
}
