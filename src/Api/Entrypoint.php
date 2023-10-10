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

namespace ApiPlatform\Api;

use ApiPlatform\Metadata\Resource\ResourceNameCollection;

/**
 * The first path you will see in the API.
 *
 * @deprecated use ApiPlatform\Documentation\Entrypoint
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class Entrypoint
{
    public function __construct(private readonly ResourceNameCollection $resourceNameCollection)
    {
    }

    public function getResourceNameCollection(): ResourceNameCollection
    {
        return $this->resourceNameCollection;
    }
}
