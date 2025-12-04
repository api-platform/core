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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\WebLink\Link;

#[ApiResource(
    operations: [new GetCollection(
        headers: [
            'deprecation' => '@1688169599',
            'sunset' => 'Sun, 30 Jun 2024 23:59:59 UTC',
        ],
        links: [
            new Link('deprecation', 'https://developer.example.com/deprecation'),
        ],
        deprecationReason: 'This API is deprecated',
        provider: [self::class, 'provide'],
    )],
)]
class DeprecationHeader
{
    public function __construct(#[ApiProperty(identifier: true)] public int $id)
    {
    }

    public static function provide(): iterable
    {
        return [
            new self(1),
            new self(2),
        ];
    }
}
