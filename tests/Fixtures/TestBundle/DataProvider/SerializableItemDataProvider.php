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
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\SerializableResource;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
class SerializableItemDataProvider implements ItemDataProviderInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        if (SerializableResource::class !== $resourceClass) {
            throw new ResourceClassNotSupportedException();
        }

        return $this->serializer->deserialize(<<<'JSON'
{
    "id": 1,
    "foo": "Lorem",
    "bar": "Ipsum"
}
JSON
            , $resourceClass, 'json');
    }
}
