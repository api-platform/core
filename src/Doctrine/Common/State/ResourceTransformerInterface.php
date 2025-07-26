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

namespace ApiPlatform\Doctrine\Common\State;

use Doctrine\Persistence\ObjectManager;

interface ResourceTransformerInterface
{
    /**
     * @param object $entityOrDocument the doctrine entity or document to make a resource from
     *
     * @return object the resulting ApiResource
     */
    public function toResource(object $entityOrDocument): object;

    /**
     * @param object        $resource      the resource you want to persist
     * @param ObjectManager $objectManager the object manager to handle this kind of resources
     *
     * @return object the existing or new entity or document
     */
    public function fromResource(object $resource, ObjectManager $objectManager): object;
}
