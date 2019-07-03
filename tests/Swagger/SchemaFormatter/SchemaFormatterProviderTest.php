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
use ApiPlatform\Core\Swagger\SchemaFormatter\DefaultDefinititionNormalizer;
use ApiPlatform\Core\Swagger\SchemaFormatter\JsonApiDefinititionNormalizer;
use PHPUnit\Framework\TestCase;

class SchemaFormatterProviderTest extends TestCase
{
    public function testGetFormatter()
    {
        $schemaFormatterFactory = new ChainSchemaFormatter([
            new JsonApiDefinititionNormalizer(),
            new DefaultDefinititionNormalizer(),
        ]);
        $formatter = $schemaFormatterFactory->getFormatter('application/json');
        $this->assertInstanceOf(DefaultDefinititionNormalizer::class, $formatter);
    }

    public function testGetFormatterDefault()
    {
        $schemaFormatterFactory = new ChainSchemaFormatter([
            new JsonApiDefinititionNormalizer(),
            new DefaultDefinititionNormalizer(),
        ]);

        $formatter = $schemaFormatterFactory->getFormatter('application/json-test');
        $this->assertInstanceOf(DefaultDefinititionNormalizer::class, $formatter);
    }

    public function testGetFormatterExceptionNoFormatters()
    {
        $schemaFormatterFactory = new ChainSchemaFormatter([]);

        $this->expectException(FormatterNotFoundException::class);
        $schemaFormatterFactory->getFormatter('application/json-test');
    }
}
