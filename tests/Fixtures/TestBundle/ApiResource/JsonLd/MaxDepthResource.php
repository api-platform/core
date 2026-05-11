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
use ApiPlatform\Metadata\Post;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;

#[ApiResource(
    shortName: 'JsonLdMaxDepth',
    normalizationContext: ['groups' => ['jsonld_max_depth'], 'enable_max_depth' => true],
    denormalizationContext: ['groups' => ['jsonld_max_depth'], 'enable_max_depth' => true],
    operations: [
        new Post(
            uriTemplate: '/jsonld_max_depth_resources',
            processor: [self::class, 'process'],
        ),
    ],
)]
class MaxDepthResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['jsonld_max_depth'])]
    public ?int $id = null;

    #[Groups(['jsonld_max_depth'])]
    public ?string $name = null;

    #[Groups(['jsonld_max_depth'])]
    #[MaxDepth(1)]
    public ?self $child = null;

    public static function process(self $data): self
    {
        $data->id = 1;
        if ($data->child) {
            $data->child->id = 2;
            if ($data->child->child) {
                $data->child->child->id = 3;
            }
        }

        return $data;
    }
}
