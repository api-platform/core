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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonSchemaGolden;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GoldenBook',
    operations: [
        new Get(
            uriTemplate: '/golden_books/{id}',
            name: 'golden_book_get',
            description: 'Retrieves a golden book.',
            deprecationReason: 'Use another book endpoint.',
            types: ['https://schema.org/Book'],
            normalizationContext: ['groups' => ['golden:read']],
            provider: [self::class, 'provide'],
        ),
        new GetCollection(
            uriTemplate: '/golden_books',
            name: 'golden_book_get_collection',
            normalizationContext: ['groups' => ['golden:read']],
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class GoldenBook
{
    #[ApiProperty(identifier: true)]
    #[Groups(['golden:read'])]
    public ?int $id = null;

    #[Groups(['golden:read'])]
    public string $title = '';

    #[ApiProperty(description: 'International standard book number.', deprecationReason: 'Use title.', example: '9780000000000')]
    #[Groups(['golden:read'])]
    public ?string $isbn = null;

    public string $internalNote = '';

    public static function provide(): null
    {
        return null;
    }
}
