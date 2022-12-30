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

namespace ApiPlatform\Tests\Fixtures\Elasticsearch\Model;

use ApiPlatform\Elasticsearch\Filter\MatchFilter;
use ApiPlatform\Elasticsearch\Filter\OrderFilter;
use ApiPlatform\Elasticsearch\Metadata\ElasticsearchDocument;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['book:read']], persistenceMeans: new ElasticsearchDocument('book'))]
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
