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
 * Reproducer fixture: a tool declaring its format as a LIST rather than a map.
 *
 * `['json']` means "reuse the already configured `json` format", and every HTTP operation
 * gets it resolved to `['json' => ['application/json']]` by normalizeFormats(). MCP
 * operations used to skip that normalization entirely, leaving `[0 => 'json']`.
 */
#[McpTool(
    name: 'format_message_list',
    description: 'Echo a message, with the output format declared as a list',
    outputFormats: ['json'],
    processor: [McpFormatListTool::class, 'process'],
)]
class McpFormatListTool
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
