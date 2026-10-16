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

#[ApiResource(
    shortName: 'GoldenNested',
    operations: [
        new Get(
            uriTemplate: '/golden_nested/{id}',
            name: 'golden_nested_get_attributes',
            normalizationContext: ['attributes' => ['name', 'author' => ['name']]],
            provider: [self::class, 'provide'],
        ),
        new Get(
            uriTemplate: '/golden_nested_ignored/{id}',
            name: 'golden_nested_get_ignored',
            normalizationContext: ['ignored_attributes' => ['secret']],
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class GoldenNestedResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $name = '';

    public string $secret = '';

    public ?GoldenAuthor $author = null;

    public static function provide(): null
    {
        return null;
    }
}
