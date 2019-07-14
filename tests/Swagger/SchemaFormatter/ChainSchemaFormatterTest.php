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
use ApiPlatform\Core\Swagger\SchemaFormatter\ChainSchemaFormatter;
use ApiPlatform\Core\Swagger\SchemaFormatter\DefaultDefinitionNormalizer;
use ApiPlatform\Core\Swagger\SchemaFormatter\HydraDefinitionNormalizer;
use ApiPlatform\Core\Swagger\SchemaFormatter\JsonApiDefinitionNormalizer;
use PHPUnit\Framework\TestCase;

class ChainSchemaFormatterTest extends TestCase
{
    public function testGetFormatter()
    {
        $schemaFormatterFactory = new ChainSchemaFormatter([
            new HydraDefinitionNormalizer(),
            new JsonApiDefinitionNormalizer(),
            new DefaultDefinitionNormalizer(),
        ]);
        $formatter = $schemaFormatterFactory->getFormatter('application/json');
        $this->assertInstanceOf(DefaultDefinitionNormalizer::class, $formatter);
    }

    public function testGetFormatterDefault()
    {
        $schemaFormatterFactory = new ChainSchemaFormatter([
            new HydraDefinitionNormalizer(),
            new JsonApiDefinitionNormalizer(),
            new DefaultDefinitionNormalizer(),
        ]);

        $formatter = $schemaFormatterFactory->getFormatter('application/json-test');
        $this->assertInstanceOf(DefaultDefinitionNormalizer::class, $formatter);
    }

    public function testGetFormatterExceptionNoFormatters()
    {
        $schemaFormatterFactory = new ChainSchemaFormatter([]);

        $this->expectException(FormatterNotFoundException::class);
        $schemaFormatterFactory->getFormatter('application/json-test');
    }
}
