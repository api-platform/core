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
use ApiPlatform\Metadata\McpTool;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;

#[ApiResource(
    shortName: 'McpWithMarkdown',
    operations: [],
    mcp: [
        'generate_markdown' => new McpTool(
            description: 'Generate markdown documentation',
            processor: [self::class, 'process'],
            structuredContent: false
        ),
    ]
)]
class McpWithMarkdown
{
    public function __construct(
        private string $title,
        private string $content,
        private bool $includeCodeBlock = false,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function isIncludeCodeBlock(): bool
    {
        return $this->includeCodeBlock;
    }

    public function setIncludeCodeBlock(bool $includeCodeBlock): void
    {
        $this->includeCodeBlock = $includeCodeBlock;
    }

    public static function process($data): CallToolResult
    {
        $markdown = "# {$data->getTitle()}\n\n";
        $markdown .= $data->getContent();

        if ($data->isIncludeCodeBlock()) {
            $markdown .= "\n\n```php\n";
            $markdown .= "echo 'Hello, World!';\n";
            $markdown .= '```';
        }

        return new CallToolResult(
            [new TextContent($markdown)],
            false
        );
    }
}
