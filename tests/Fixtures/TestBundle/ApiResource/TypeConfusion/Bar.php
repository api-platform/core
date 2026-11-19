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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\TypeConfusion;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/type-confusion/bars/{id}{._format}', provider: [self::class, 'provide']),
    ]
)]
class Bar
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public readonly int $id,
        public readonly string $name = 'bar',
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self
    {
        return new self((int) ($uriVariables['id'] ?? 1));
    }
}
