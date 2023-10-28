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
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;

#[Patch(
    processor: [Beta::class, 'process'],
    provider: [Beta::class, 'provide'],
)]
final class Beta
{
    public function __construct(#[ApiProperty(identifier: true)] public int $betaId, public ?Alpha $alpha = null)
    {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        return new self(betaId: $uriVariables['betaId']);
    }

    public static function process($body)
    {
        return $body;
    }
}
