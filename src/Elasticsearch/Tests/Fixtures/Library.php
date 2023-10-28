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

use ApiPlatform\Elasticsearch\Filter\TermFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(normalizationContext: ['groups' => ['library:read']])]
#[ApiFilter(TermFilter::class, properties: ['id', 'gender', 'age', 'firstName', 'books.id', 'books.date'])]
class Library
{
    #[ApiProperty(identifier: true)]
    #[Groups(['book:read', 'library:read'])]
    public ?string $id = null;

    #[Groups(['book:read', 'library:read'])]
    public ?string $gender = null;

    #[Groups(['book:read', 'library:read'])]
    public ?int $age = null;

    #[Groups(['book:read', 'library:read'])]
    public ?string $firstName = null;

    #[Groups(['book:read', 'library:read'])]
    public ?string $lastName = null;

    #[Groups(['library:read'])]
    public ?\DateTimeInterface $registeredAt = null;

    /** @var Book[] */
    #[Groups(['library:read'])]
    public array $books = [];
}
