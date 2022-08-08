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
     * @return Link[]
     */
    public function createLinksFromIdentifiers(ApiResource|Operation $operation);

    /**
     * Create Links from the relations metadata information.
     *
     * @return Link[]
     */
    public function createLinksFromRelations(ApiResource|Operation $operation);

    /**
     * Create Links by using PHP attribute Links found on properties.
     *
     * @return Link[]
     */
    public function createLinksFromAttributes(ApiResource|Operation $operation): array;

    /**
     * Complete a link with identifiers information.
     */
    public function completeLink(Link $link): Link;
}
