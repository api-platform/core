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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DiscriminatedBookAction;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/discriminated_book_actions',
            input: BookAction::class,
            processor: [self::class, 'process'],
        ),
    ]
)]
final class BookActionResource
{
    public ?int $id = 1;

    public ?string $performed = null;

    public static function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $resource = new self();
        $resource->performed = $data::class;

        return $resource;
    }
}
