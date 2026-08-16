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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Operation;

/**
 * Link target of the "secured_uri_variable_tool" of {@see McpSecuredTools}: link security first
 * reads the related resource, then evaluates the expression carried by the Link.
 */
#[ApiResource(
    shortName: 'McpSecuredReference',
    operations: [
        new Get(
            uriTemplate: '/mcp_secured_references/{reference}',
            uriVariables: ['reference'],
            provider: [self::class, 'provide']
        ),
    ]
)]
class McpSecuredReference
{
    public function __construct(
        public ?string $reference = null,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    public static function provide(Operation $operation, array $uriVariables = []): self
    {
        return new self($uriVariables['reference'] ?? null);
    }
}
