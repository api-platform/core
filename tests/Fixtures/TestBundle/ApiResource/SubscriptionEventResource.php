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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\QueryCollection;
use ApiPlatform\Metadata\GraphQl\Subscription;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource(
    operations: [new Get()],
    graphQlOperations: [new Query(), new QueryCollection(paginationType: 'page'), new Subscription(name: 'watch')],
    mercure: true,
)]
final class SubscriptionEventResource
{
    #[ApiProperty(identifier: true)]
    public int $id;
    public string $name;

    /** @var Collection<int, self> */
    #[ApiProperty(readableLink: true)]
    public Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}
