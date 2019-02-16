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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Tests\Fixtures\NotAResource;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\ContainNonResource as ContainNonResourceDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ContainNonResource;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ContainNonResourceItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return \in_array($resourceClass, [ContainNonResource::class, ContainNonResourceDocument::class], true);
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        if (!is_scalar($id)) {
            throw new \InvalidArgumentException('The id must be a scalar.');
        }

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
