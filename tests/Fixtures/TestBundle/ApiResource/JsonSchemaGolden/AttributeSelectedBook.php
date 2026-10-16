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
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'AttributeSelectedBook',
    operations: [
        new Get(
            uriTemplate: '/attribute_selected_books/{id}',
            name: 'attribute_selected_book_get',
            normalizationContext: ['attributes' => ['title', 'author' => ['name']]],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/attribute_selected_books',
            name: 'attribute_selected_book_post',
            denormalizationContext: ['attributes' => ['title', 'author' => ['name']]],
            processor: [self::class, 'process'],
        ),
    ],
)]
final class AttributeSelectedBook
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $title = '';

    public string $isbn = '';

    public ?AttributeSelectedAuthor $author = null;

    public static function provide(): null
    {
        return null;
    }

    public static function process(): null
    {
        return null;
    }
}
