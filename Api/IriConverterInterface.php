<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Api;

use Dunglas\ApiBundle\Exception\InvalidArgumentException;
use Symfony\Component\Routing\RouterInterface;

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
    public function getItemFromIri($iri, $fetchData = false);

    /**
     * Gets the IRI associated with the given item.
     *
     * @param object $item
     * @param bool   $referenceType
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getIriFromItem($item, $referenceType = RouterInterface::ABSOLUTE_PATH);

    /**
     * Gets the IRI associated with the given resource collection.
     *
     * @param ResourceInterface $resource
     * @param bool              $referenceType
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getIriFromResource(ResourceInterface $resource, $referenceType = RouterInterface::ABSOLUTE_PATH);
}
