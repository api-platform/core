<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ObjectPrimaryKey;
use DateTime;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ObjectPrimaryKeyItemDataProvider implements ItemDataProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        if (ObjectPrimaryKey::class !== $resourceClass) {
            throw new ResourceClassNotSupportedException();
        }

        $planning = new ObjectPrimaryKey();
        $planning->setDate(new DateTime($id));

        return $planning;
    }
}
