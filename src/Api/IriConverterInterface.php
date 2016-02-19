<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Api;

use ApiPlatform\Builder\Exception\InvalidArgumentException;

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
     * @param string $iri
     * @param bool   $fetchData
     *
     * @return object
     *
     * @throws InvalidArgumentException
     */
    public function getItemFromIri(string $iri, bool $fetchData = false);

    /**
     * Gets the IRI associated with the given item.
     *
     * @param object $item
     * @param string $referenceType
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getIriFromItem($item, int $referenceType = UrlGeneratorInterface::ABS_PATH) : string;

    /**
     * Gets the IRI associated with the given resource collection.
     *
     * @param string $resourceClass
     * @param string $referenceType
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getIriFromResourceClass(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH) : string;
}
