<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Action;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceNameCollection;

/**
 * Generates the API entrypoint.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EntrypointAction
{
    private $resourceNameCollectionFactory;

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollection)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollection;
    }

    public function __invoke() : ResourceNameCollection
    {
        return $this->resourceNameCollectionFactory->create();
    }
}
