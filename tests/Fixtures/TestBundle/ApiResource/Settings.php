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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use Symfony\Component\Validator\Constraints as Assert;

#[Get(uriTemplate: '/merge_patch_settings/{id}', provider: [self::class, 'provide'])]
#[Patch(
    uriTemplate: '/merge_patch_settings/{id}',
    denormalizationContext: ['api_merge_patch_maps' => true],
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
)]
#[Patch(
    uriTemplate: '/merge_patch_settings/{id}/replace',
    provider: [self::class, 'provide'],
    processor: [self::class, 'process'],
)]
class Settings
{
    public int $id;

    /**
     * @var array<string, mixed>
     */
    #[Assert\Count(max: 3)]
    public array $settings = [];

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        $settings = new self();
        $settings->id = (int) $uriVariables['id'];
        $settings->settings = ['a' => 1, 'b' => 2, 'c' => ['e' => 2]];

        return $settings;
    }

    public static function process(mixed $data): mixed
    {
        return $data;
    }
}
