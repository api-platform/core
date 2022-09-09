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
interface PropertyLinkFactoryInterface
{
    /**
     * Create a link for a given property.
     */
    public function createLinkFromProperty(ApiResource|Operation $operation, string $property): Link;
}
