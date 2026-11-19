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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\McpTool;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;

#[ApiResource(
    shortName: 'McpSecuredTools',
    operations: [],
    mcp: [
        'secured_denied_tool' => new McpTool(
            processor: [self::class, 'process'],
            policy: 'viewAny',
        ),
        'secured_model_tool' => new McpTool(
            processor: [self::class, 'process'],
            policy: 'view',
        ),
        'secured_granted_tool' => new McpTool(
            processor: [self::class, 'process'],
            policy: 'create',
        ),
    ]
)]
class McpSecuredTools
{
    public function __construct(
        private ?string $text = null,
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

    public static function process(self $data): CallToolResult
    {
        return new CallToolResult([new TextContent('processed: '.$data->getText())]);
    }
}
