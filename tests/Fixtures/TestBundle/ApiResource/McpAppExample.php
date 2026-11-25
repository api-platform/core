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
use Mcp\Schema\Extension\Apps\McpApps;

#[ApiResource(
    shortName: 'McpAppExample',
    operations: [],
    mcp: [
        'app' => new McpResource(
            uri: 'ui://api-platform/app',
            name: 'API-Platform-App',
            description: 'Interactive API Platform app',
            mimeType: McpApps::MIME_TYPE,
            provider: [self::class, 'provide']
        ),
    ]
)]
class McpAppExample
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

    public function getUri(): string
    {
        return $this->uri;
    }

    public static function provide(): self
    {
        return new self(
            '<html><body><h1>API Platform</h1></body></html>',
            'ui://api-platform/app'
        );
    }
}
