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
        new Subscription(name: 'privateItem', mercure: ['private' => true]),
        new Subscription(name: 'publicItem', mercure: ['private' => false]),
        new SubscriptionCollection(name: 'privateCollection', mercure: ['private' => true, 'private_fields' => ['tenant']]),
        new SubscriptionCollection(name: 'publicCollection', mercure: ['private' => false]),
        new SubscriptionCollection(name: 'inherited'),
    ],
    mercure: "{'private': object.restPrivate, 'enable_async_update': false}",
)]
class SubscriptionPrivacyResource
{
    #[ORM\Id, ORM\Column]
    public int $id;
    #[ORM\Column]
    public string $name = 'Initial';
    #[ORM\Column]
    public string $tenant = 'tenant-a';
    #[ORM\Column]
    public bool $restPrivate = false;
}
