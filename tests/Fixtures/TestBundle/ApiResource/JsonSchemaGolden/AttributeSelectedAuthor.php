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
    shortName: 'AttributeSelectedAuthor',
    operations: [
        new Get(
            uriTemplate: '/attribute_selected_authors/{id}',
            name: 'attribute_selected_author_get',
            provider: [self::class, 'provide'],
        ),
    ],
)]
final class AttributeSelectedAuthor
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public string $name = '';

    public string $email = '';

    public static function provide(): null
    {
        return null;
    }
}
