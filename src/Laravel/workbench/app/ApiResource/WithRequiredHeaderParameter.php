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

namespace Workbench\App\ApiResource;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameters;

#[Get(
    uriTemplate: '/with_required_header_parameter/{id}',
    uriVariables: ['id'],
    parameters: new Parameters([
        new HeaderParameter(key: 'X-Foo', required: true, constraints: 'required'),
    ]),
    provider: [self::class, 'provide'],
)]
class WithRequiredHeaderParameter
{
    public function __construct(
        public int $id,
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        return new self((int) $uriVariables['id']);
    }
}
