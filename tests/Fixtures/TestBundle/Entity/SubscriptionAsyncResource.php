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
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    operations: [new Get(mercure: ['hub' => 'rest'])],
    graphQlOperations: [
        new Query(),
        new SubscriptionCollection(name: 'watch', mercure: ['private' => true, 'hub' => 'graphql']),
    ],
)]
class SubscriptionAsyncResource
{
    #[ORM\Id, ORM\Column]
    public int $id;
    #[ORM\Column]
    public string $name = 'Initial';
}
