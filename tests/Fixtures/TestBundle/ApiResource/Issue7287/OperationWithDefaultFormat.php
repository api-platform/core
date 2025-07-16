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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7287;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\NotExposed;

#[ApiResource(
    outputFormats: ['jsonld'],
    operations: [
        new GetCollection(provider: [self::class, 'provide']),
        new NotExposed(uriVariables: ['id']),
    ]
)]
class OperationWithDefaultFormat
{
    public function __construct(
        public string $id,
    ) {
    }

    public static function provide(): array
    {
        return [new self('1')];
    }
}
