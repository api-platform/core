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

#[ApiResource(
    shortName: 'HalRelatedResource',
    operations: [
        new Get(
            uriTemplate: '/hal_related_resources/{id}',
            uriVariables: ['id'],
            provider: [self::class, 'provide'],
        ),
    ],
)]
class HalRelatedResource
{
    #[ApiProperty(identifier: true)]
    public int $id = 1;

    public string $symfony = 'symfony';

    #[ApiProperty(readableLink: true)]
    public ?HalThirdLevel $thirdLevel = null;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        $r = new self();
        $r->id = (int) ($uriVariables['id'] ?? 1);
        $r->thirdLevel = HalThirdLevel::provide(new Get(), ['id' => 1], $context);

        return $r;
    }
}
