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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\NotExposed;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'JsonLdNoOutputMessage',
    operations: [
        new NotExposed(uriTemplate: '/jsonld_no_output_messages/{id}'),
        new Post(
            uriTemplate: '/jsonld_no_output_messages',
            status: 202,
            output: false,
            processor: [self::class, 'process'],
        ),
    ],
)]
class NoOutputMessage
{
    public ?int $id = null;

    public static function process(mixed $data): mixed
    {
        return $data;
    }
}
