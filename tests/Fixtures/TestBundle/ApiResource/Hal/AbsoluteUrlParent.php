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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Hal;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\UrlGeneratorInterface;

#[ApiResource(
    shortName: 'HalAbsoluteUrlParent',
    urlGenerationStrategy: UrlGeneratorInterface::ABS_URL,
    operations: [
        new Get(
            uriTemplate: '/hal_absolute_url_parents/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
        new Post(
            uriTemplate: '/hal_absolute_url_parents',
            processor: [self::class, 'process'],
        ),
    ],
)]
class AbsoluteUrlParent
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    /** @var AbsoluteUrlChild[] */
    public array $children = [];

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);

        return $r;
    }

    public static function process(self $data): self
    {
        $data->id = 2;

        return $data;
    }
}
