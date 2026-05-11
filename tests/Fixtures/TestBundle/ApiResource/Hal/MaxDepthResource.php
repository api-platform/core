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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;

#[ApiResource(
    shortName: 'HalMaxDepth',
    normalizationContext: ['groups' => ['hal_max_depth'], 'enable_max_depth' => true],
    denormalizationContext: ['groups' => ['hal_max_depth'], 'enable_max_depth' => true],
    operations: [
        new Post(
            uriTemplate: '/hal_max_depth_resources',
            processor: [self::class, 'process'],
        ),
        new Put(
            uriTemplate: '/hal_max_depth_resources/{id}',
            uriVariables: ['id'],
            extraProperties: ['standard_put' => false],
            provider: [self::class, 'provide'],
            processor: [self::class, 'process'],
        ),
    ],
)]
class MaxDepthResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['hal_max_depth'])]
    public ?int $id = null;

    #[Groups(['hal_max_depth'])]
    public ?string $name = null;

    #[Groups(['hal_max_depth'])]
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

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $root = new self();
        $root->id = (int) ($uriVariables['id'] ?? 1);
        $root->name = 'level 1';
        $root->child = new self();
        $root->child->id = 2;
        $root->child->name = 'level 2';

        return $root;
    }
}
