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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ApiResource(
    operations: [new Get()],
    graphQlOperations: [new Query(), new QueryCollection(paginationType: 'page'), new Subscription(name: 'watch')],
    mercure: true,
    provider: [self::class, 'provide'],
)]
final class SubscriptionFingerprintResource
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

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self|ArrayPaginator
    {
        $parent = new self();
        $parent->id = (int) ($uriVariables['id'] ?? 1);
        $parent->name = 'Parent';
        $child = new self();
        $child->id = 2;
        $child->name = 'Child';
        if (1 === $parent->id) {
            $parent->children->add($child);
        }
        if ($operation instanceof QueryCollection) {
            return new ArrayPaginator($parent->children->toArray(), 0, 3);
        }

        return $parent;
    }
}
