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

use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Metadata;

/**
 * @internal
 */
interface PropertyLinkFactoryInterface
{
    /**
     * Create a link for a given property.
     */
    public function createLinkFromProperty(Metadata $operation, string $property): Link;
}
