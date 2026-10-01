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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

/**
 * Abstract resource without a serializer discriminator map: subclass properties must stay hidden.
 *
 * @see https://github.com/api-platform/core/issues/2931
 */
#[ApiResource(operations: [new Get(provider: [self::class, 'provide'])])]
abstract class UndiscriminatedBook
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $title = '';

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $book = new UndiscriminatedSecretBook();
        $book->id = (int) $uriVariables['id'];
        $book->title = 'Hidden';
        $book->secret = 'must not leak';

        return $book;
    }
}
