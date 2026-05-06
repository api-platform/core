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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\State\Pagination\ArrayPaginator;

#[ApiResource(
    shortName: 'JsonLdAbsolutePaged',
    urlGenerationStrategy: UrlGeneratorInterface::ABS_URL,
    paginationItemsPerPage: 3,
    operations: [
        new GetCollection(
            uriTemplate: '/jsonld_absolute_paged',
            provider: [self::class, 'provideCollection'],
        ),
    ],
)]
class AbsolutePagedResource
{
    #[ApiProperty(identifier: true)]
    public int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator
    {
        $page = (int) ($context['filters']['page'] ?? 1);
        $items = array_map(static fn (int $i): self => new self($i), range(1, 30));

        return new ArrayPaginator($items, ($page - 1) * 3, 3);
    }
}
