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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;

/**
 * @internal
 */
interface LinkFactoryInterface
{
    /**
     * Create Links by using the resource class identifiers.
     *
     * @param ApiResource|Operation $operation
     *
     * @return Link[]
     */
    public function createLinksFromIdentifiers($operation);

    /**
     * Create Links from the relations metadata information.
     *
     * @param ApiResource|Operation $operation
     *
     * @return Link[]
     */
    public function createLinksFromRelations($operation);

    /**
     * Create Links by using PHP attribute Links found on properties.
     *
     * @param ApiResource|Operation $operation
     *
     * @return Link[]
     */
    public function createLinksFromAttributes($operation): array;

    /**
     * Complete a link with identifiers information.
     */
    public function completeLink(Link $link): Link;
}
