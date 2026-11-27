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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PotentialAction;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/actionable_books/{bookId}/notes/{id}',
            uriVariables: [
                'bookId' => new Link(fromClass: ActionableBook::class, toProperty: 'book'),
                'id' => new Link(fromClass: self::class),
            ],
            provider: [self::class, 'provide'],
        ),
        new GetCollection(
            uriTemplate: '/actionable_books/{bookId}/notes',
            uriVariables: ['bookId' => new Link(fromClass: ActionableBook::class, toProperty: 'book')],
            provider: [self::class, 'provideCollection'],
        ),
        new Post(
            uriTemplate: '/actionable_books/{bookId}/notes',
            uriVariables: ['bookId' => new Link(fromClass: ActionableBook::class, toProperty: 'book')],
            processor: [self::class, 'process'],
            potentialAction: true,
        ),
    ]
)]
final class ActionableBookNote
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public ?ActionableBook $book = null;

    #[Assert\NotBlank]
    public ?string $body = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $note = new self();
        $note->id = (int) $uriVariables['id'];
        $note->body = 'Noted';

        return $note;
    }

    /**
     * @return list<self>
     */
    public static function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return [];
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        return $data;
    }
}
