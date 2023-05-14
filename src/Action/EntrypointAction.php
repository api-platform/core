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

namespace ApiPlatform\Action;

use ApiPlatform\Api\Entrypoint;
use ApiPlatform\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;

/**
 * Generates the API entrypoint.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class EntrypointAction
{
    public function __construct(private readonly ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory)
    {
    }

    public function __invoke(): Entrypoint
    {
        return new Entrypoint($this->resourceNameCollectionFactory->create());
    }
}
