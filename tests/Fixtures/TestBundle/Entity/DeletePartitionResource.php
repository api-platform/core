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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    operations: [new Get()],
    graphQlOperations: [
        new Query(),
        new Subscription(name: 'byTenant', mercure: ['private' => true, 'private_fields' => ['tenant']]),
        new Subscription(name: 'shared', mercure: ['private' => true]),
        new SubscriptionCollection(name: 'byChat', mercure: ['private' => true, 'private_fields' => ['tenant', 'chat']]),
        new SubscriptionCollection(name: 'byChatReverse', mercure: ['private' => true, 'private_fields' => ['chat', 'tenant']]),
        new SubscriptionCollection(name: 'unpartitioned', mercure: ['private' => true]),
    ],
    mercure: ['private' => true, 'enable_async_update' => false],
)]
class DeletePartitionResource
{
    #[ORM\Id, ORM\Column]
    public int $id;
    #[ORM\Column]
    public string $tenant = 'tenant-a';
    #[ORM\Column]
    public string $chat = 'chat-a';
}
