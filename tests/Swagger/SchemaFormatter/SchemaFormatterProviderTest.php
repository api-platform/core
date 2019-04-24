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

namespace ApiPlatform\Core\Tests\Swagger\SchemaFormatter;

use ApiPlatform\Core\Exception\FormatterNotFoundException;
use ApiPlatform\Core\Swagger\SchemaFormatter\JsonApiSchemaFormatter;
use ApiPlatform\Core\Swagger\SchemaFormatter\JsonSchemaFormatter;
use ApiPlatform\Core\Swagger\SchemaFormatter\SchemaFormatterProvider;
use PHPUnit\Framework\TestCase;

class SchemaFormatterProviderTest extends TestCase
{
    public function testGetFormatter()
    {
        $schemaFormatterFactory = new SchemaFormatterProvider([
            new JsonApiSchemaFormatter(),
            new JsonSchemaFormatter(),
        ]);
        $formatter = $schemaFormatterFactory->getFormatter('application/json');
        $this->assertInstanceOf(JsonSchemaFormatter::class, $formatter);
    }

    public function testGetFormatterException()
    {
        $schemaFormatterFactory = new SchemaFormatterProvider([
            new JsonApiSchemaFormatter(),
            new JsonSchemaFormatter(),
        ]);

        $this->expectException(FormatterNotFoundException::class);
        $schemaFormatterFactory->getFormatter('application/json-test');
    }

    public function testGetFormatterExceptionNoFormatters()
    {
        $schemaFormatterFactory = new SchemaFormatterProvider([]);

        $this->expectException(FormatterNotFoundException::class);
        $schemaFormatterFactory->getFormatter('application/json-test');
    }
}
