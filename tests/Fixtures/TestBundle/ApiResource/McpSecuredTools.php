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
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\McpTool;

#[ApiResource(
    shortName: 'McpSecuredTools',
    operations: [],
    mcp: [
        'secured_tool' => new McpTool(
            security: "is_granted('ROLE_ADMIN')",
            processor: [self::class, 'process']
        ),
        'secured_post_denormalize_tool' => new McpTool(
            securityPostDenormalize: "is_granted('ROLE_ADMIN')",
            processor: [self::class, 'process']
        ),
        'secured_post_validation_tool' => new McpTool(
            validate: true,
            securityPostValidation: "is_granted('ROLE_ADMIN')",
            processor: [self::class, 'process']
        ),
        'secured_uri_variable_tool' => new McpTool(
            uriVariables: [
                'reference' => new Link(fromClass: McpSecuredReference::class, security: "is_granted('ROLE_ADMIN')"),
            ],
            processor: [self::class, 'process']
        ),
    ]
)]
class McpSecuredTools
{
    public function __construct(
        private ?string $text = null,
        private ?string $reference = null,
    ) {
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): void
    {
        $this->reference = $reference;
    }

    public static function process($data): mixed
    {
        $data->setText('Secured: '.$data->getText());

        return $data;
    }
}
