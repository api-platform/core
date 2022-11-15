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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[GetCollection('/item_referenced_in_collection{._format}', itemUriTemplate: '/item_referenced_in_collection/{id}{._format}', provider: [CollectionReferencingItem::class, 'getData'])]
class CollectionReferencingItem
{
    public function __construct(public string $id, public string $name)
    {
    }

    public static function getData(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return [new self('a', 'hello'), new self('b', 'you')];
    }
}
