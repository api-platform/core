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

use Dunglas\JsonLdApiBundle\Resource;

/**
 * Manipulates data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface DataManipulatorInterface
{
    /**
     * Retrieves a collection.
     *
     * @param Resource $resource
     * @param int $page
     * @param array $filters
     * @param int|null $byPage
     * @param string|null $order
     *
     * @return mixed
     */
    public function getCollection(Resource $resource, $page, array $filters, $byPage = null, $order = null);

    /**
     * Gets an URI corresponding to an object.
     *
     * @param object $object
     * @param string $type
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getUriFromObject($object, $type);

    /**
     * Gets object from an URI.
     *
     * @param string $uri
     *
     * @return object
     *
     * @throws \InvalidArgumentException
     */
    public function getObjectFromUri($uri);
}
