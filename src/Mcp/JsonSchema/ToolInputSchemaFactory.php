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

namespace ApiPlatform\Mcp\JsonSchema;

use ApiPlatform\JsonSchema\Schema;
use ApiPlatform\JsonSchema\SchemaFactory;
use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Metadata\McpTool;

/**
 * @experimental
 */
final class ToolInputSchemaFactory
{
    public function __construct(private readonly SchemaFactoryInterface $schemaFactory)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function create(McpTool $tool): array
    {
        $inputClass = $tool->getInput()['class'] ?? $tool->getClass();
        $inputFormat = array_key_first($tool->getInputFormats() ?? ['json' => ['application/json']]);

        return $this->schemaFactory->buildSchema($inputClass, $inputFormat, Schema::TYPE_INPUT, $tool, null, [SchemaFactory::FORCE_SUBSCHEMA => true])->getArrayCopy();
    }
}
