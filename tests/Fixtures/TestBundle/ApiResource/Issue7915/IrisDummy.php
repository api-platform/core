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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7915;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;

#[ApiResource(
    operations: [
        new Get(uriTemplate: '/iris_dummies/{id}', provider: [self::class, 'provide']),
        new GetCollection(uriTemplate: '/iris_dummies', provider: [self::class, 'provide']),
    ],
)]
class IrisDummy
{
    public int $id = 1;

    public static function provide(Operation $operation, array $uriVariables = [], array $context = []): self|array
    {
        return $operation instanceof GetCollection ? [new self()] : new self();
    }
}
