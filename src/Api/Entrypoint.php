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
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class Entrypoint
{
    private $resourceNameCollection;

    public function __construct(ResourceNameCollection $resourceNameCollection)
    {
        $this->resourceNameCollection = $resourceNameCollection;
    }

    public function getResourceNameCollection(): ResourceNameCollection
    {
        return $this->resourceNameCollection;
    }
}

class_alias(Entrypoint::class, \ApiPlatform\Core\Api\Entrypoint::class);
