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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;

#[ApiResource(
    shortName: 'JsonLdPlainObjectResource',
    operations: [
        new Get(
            uriTemplate: '/jsonld_plain_object_resources/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/jsonld_plain_object_resources',
            processor: [self::class, 'process'],
        ),
    ],
)]
class PlainObjectResource
{
    #[ApiProperty(identifier: true)]
    public ?int $id = null;

    public ?string $content = null;

    public ?\stdClass $data = null;

    public static function provide(): self
    {
        return new self();
    }

    public static function process(self $data): self
    {
        $data->id = 1;
        if (null !== $data->content) {
            $data->data = json_decode($data->content);
        }

        return $data;
    }
}
