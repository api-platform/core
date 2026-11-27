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
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/actionable_books/{id}', provider: [self::class, 'provide']),
        new GetCollection(uriTemplate: '/actionable_books', provider: [self::class, 'provideCollection']),
        new Post(uriTemplate: '/actionable_books', processor: [self::class, 'process'], potentialAction: true),
        new Post(
            uriTemplate: '/actionable_books/{id}/reviews',
            uriVariables: ['id' => new Link(fromClass: self::class)],
            input: BookReviewAction::class,
            output: false,
            provider: [self::class, 'provide'],
            processor: [self::class, 'process'],
            potentialAction: true,
        ),
        new Post(
            uriTemplate: '/actionable_books/{id}/archive',
            uriVariables: ['id' => new Link(fromClass: self::class)],
            input: ArchiveBookInput::class,
            output: false,
            provider: [self::class, 'provide'],
            processor: [self::class, 'process'],
            potentialAction: 'ArchiveAction',
        ),
        new Patch(uriTemplate: '/actionable_books/{id}', provider: [self::class, 'provide'], processor: [self::class, 'process'], potentialAction: true),
        new Delete(uriTemplate: '/actionable_books/{id}', provider: [self::class, 'provide'], processor: [self::class, 'process'], potentialAction: true),
        new Post(
            uriTemplate: '/actionable_books/{id}/lock',
            uriVariables: ['id' => new Link(fromClass: self::class)],
            input: ArchiveBookInput::class,
            output: false,
            security: 'false',
            provider: [self::class, 'provide'],
            processor: [self::class, 'process'],
            potentialAction: true,
        ),
        new Post(
            uriTemplate: '/actionable_books/{id}/restore',
            uriVariables: ['id' => new Link(fromClass: self::class)],
            input: ArchiveBookInput::class,
            output: false,
            provider: [self::class, 'provide'],
            processor: [self::class, 'process'],
        ),
    ]
)]
final class ActionableBook
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    #[Assert\NotBlank]
    public ?string $title = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $book = new self();
        $book->id = (int) $uriVariables['id'];
        $book->title = 'Candide';

        return $book;
    }

    /**
     * @return list<self>
     */
    public static function provideCollection(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return [self::provide($operation, ['id' => 1])];
    }

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        return $data;
    }
}
