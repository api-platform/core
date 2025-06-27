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
use ApiPlatform\Metadata\Operation;

#[Get(
    uriTemplate: '/issue_7194_requirements/{key}',
    uriVariables: ['key'],
    requirements: ['key' => '\d+'],
    provider: [self::class, 'provide']
)]
class UriRequirements
{
    public function __construct(
        public int $key,
    ) {
    }

    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        return new self((int) $uriVariables['key']);
    }
}
