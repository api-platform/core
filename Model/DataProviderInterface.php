<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Model;

use Dunglas\JsonLdApiBundle\Api\ResourceInterface;

/**
 * Data provider interface.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface DataProviderInterface
{
    /**
     * Retrieves an item.
     *
     * @param ResourceInterface $resource
     * @param int|string        $id
     * @param bool              $fetchData
     *
     * @return object|null
     */
    public function getItem(ResourceInterface $resource, $id, $fetchData = false);

    /**
     * Retrieves an item from its IRI.
     *
     * @param string $iri
     * @param bool   $fetchData
     *
     * @return object|null
     */
    public function getItemFromIri($iri, $fetchData = false);

    /**
     * Retrieves a collection.
     *
     * @param array    $filters
     * @param int|null $page
     * @param int|null $itemsPerPage
     * @param array    $order
     *
     * @return PaginatorInterface|array|\Traversable
     */
    public function getCollection(ResourceInterface $resource, array $filters = [], array $order = [], $page = null, $itemsPerPage = null);

    /**
     * Does this DataProvider supports the given resource.
     *
     * @param ResourceInterface $resource
     *
     * @return bool
     */
    public function supports(ResourceInterface $resource);
}
