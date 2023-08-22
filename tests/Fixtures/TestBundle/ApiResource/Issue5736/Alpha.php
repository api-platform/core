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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5736;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

#[Get(
    provider: [Alpha::class, 'provide'],
)]
final class Alpha
{
    public function __construct(#[ApiProperty(identifier: true)] public int $alphaId)
    {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        return new self(alphaId: $uriVariables['alphaId']);
    }
}
