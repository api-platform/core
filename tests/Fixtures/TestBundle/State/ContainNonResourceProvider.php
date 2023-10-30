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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Tests\Fixtures\NotAResource;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ContainNonResourceProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = [])
    {
        $id = $uriVariables['id'] ?? null;
        if (!\is_scalar($id)) {
            throw new \InvalidArgumentException('The id must be a scalar.');
        }

        $resourceClass = $operation->getClass();
        // Retrieve the blog post item from somewhere
        $cnr = new $resourceClass();
        $cnr->id = $id;
        $cnr->notAResource = new NotAResource('f1', 'b1');
        $cnr->nested = new $resourceClass();
        $cnr->nested->id = "$id-nested";
        $cnr->nested->notAResource = new NotAResource('f2', 'b2');

        return $cnr;
    }
}
