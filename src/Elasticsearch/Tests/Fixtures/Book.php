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

namespace ApiPlatform\Elasticsearch\Tests\Fixtures;

use ApiPlatform\Elasticsearch\Filter\MatchFilter;
use ApiPlatform\Elasticsearch\Filter\OrderFilter;
use ApiPlatform\Elasticsearch\State\CollectionProvider;
use ApiPlatform\Elasticsearch\State\ItemProvider;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(operations: [new Get(provider: ItemProvider::class), new GetCollection(provider: CollectionProvider::class)], normalizationContext: ['groups' => ['book:read']])]
#[ApiFilter(OrderFilter::class, properties: ['id', 'library.id'])]
#[ApiFilter(MatchFilter::class, properties: ['message', 'library.firstName'])]
class Book
{
    #[Groups(['book:read', 'library:read'])]
    #[ApiProperty(identifier: true)]
    public ?string $id = null;

    #[Groups(['book:read'])]
    public ?Library $library = null;

    #[Groups(['book:read', 'library:read'])]
    public ?\DateTimeImmutable $date = null;

    #[Groups(['book:read', 'library:read'])]
    public ?string $message = null;
}
