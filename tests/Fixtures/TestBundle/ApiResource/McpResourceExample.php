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
use ApiPlatform\Metadata\McpResource;

#[ApiResource(
    shortName: 'McpResourceExample',
    operations: [],
    mcp: [
        'resource_doc' => new McpResource(
            uri: 'resource://api-platform/documentation',
            name: 'API-Platform-Documentation',
            description: 'Official API Platform documentation',
            mimeType: 'text/markdown',
            provider: [self::class, 'provide']
        ),
    ]
)]
class McpResourceExample
{
    public function __construct(
        private string $content,
        private string $uri,
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public static function provide(): self
    {
        return new self(
            '# API Platform Documentation\n\nThis is a sample documentation resource.',
            'resource://api-platform/documentation'
        );
    }
}
