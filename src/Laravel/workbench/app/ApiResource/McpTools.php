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
    shortName: 'McpTools',
    operations: [],
    mcp: [
        'custom_result' => new McpTool(
            processor: [self::class, 'processCustomResult']
        ),
        'validate_input' => new McpTool(
            processor: [self::class, 'processValidation'],
            rules: [
                'name' => 'required|min:3|max:50',
                'email' => 'required|email',
                'age' => 'required|integer|min:1',
            ]
        ),
    ]
)]
class McpTools
{
    public function __construct(
        private ?string $text = null,
        private ?bool $includeMetadata = null,
        private ?string $name = null,
        private ?string $email = null,
        private ?int $age = null,
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

    public function isIncludeMetadata(): ?bool
    {
        return $this->includeMetadata;
    }

    public function setIncludeMetadata(?bool $includeMetadata): void
    {
        $this->includeMetadata = $includeMetadata;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): void
    {
        $this->age = $age;
    }

    public static function processCustomResult($data): CallToolResult
    {
        $metadata = $data->isIncludeMetadata() ? ['processed' => true, 'timestamp' => time()] : null;

        return new CallToolResult(
            [new TextContent('Custom result: '.$data->getText())],
            false,
            $metadata
        );
    }

    public static function processValidation($data): mixed
    {
        $data->setName('Valid: '.$data->getName());

        return $data;
    }
}
