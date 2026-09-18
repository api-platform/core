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

use ApiPlatform\Metadata\McpTool;

/**
 * Reproducer fixture: a tool that explicitly asks for the `json` format.
 *
 * `api_platform.mcp.format` is applied by FormatsResourceMetadataCollectionFactory as
 * `$operation->withInputFormats(...)->withOutputFormats(...)`, i.e. the global option is
 * implemented by writing the operation's own formats. Declaring them here therefore
 * exercises the same code path as the global option, without touching the test app's
 * shared configuration.
 */
#[McpTool(
    name: 'format_message',
    description: 'Echo a message, serialized with the format declared on the operation',
    outputFormats: ['json' => ['application/json']],
    processor: [McpFormatTool::class, 'process'],
)]
class McpFormatTool
{
    public function __construct(
        private string $message = '',
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public static function process($data): mixed
    {
        return $data;
    }
}
