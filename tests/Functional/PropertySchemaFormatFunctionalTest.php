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

namespace ApiPlatform\Tests\Functional;

use ApiPlatform\JsonSchema\SchemaFactoryInterface;
use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PropertySchemaFormatResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class PropertySchemaFormatFunctionalTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [PropertySchemaFormatResource::class];
    }

    public function testJsonSchemaFormats(): void
    {
        /** @var SchemaFactoryInterface $schemaFactory */
        $schemaFactory = self::getContainer()->get('api_platform.json_schema.schema_factory');
        $schema = $schemaFactory->buildSchema(PropertySchemaFormatResource::class);

        $properties = $schema['definitions']['PropertySchemaFormatResource']['properties'] ?? null;
        $this->assertIsArray($properties);

        $this->assertSame('email', $properties['email']['format'] ?? null);
        $this->assertSame('uri', $properties['url']['format'] ?? null);
        $this->assertSame('hostname', $properties['hostname']['format'] ?? null);
        $this->assertSame('uuid', $properties['uuid']['format'] ?? null);
        $this->assertSame('ulid', $properties['ulid']['format'] ?? null);
        $this->assertSame('ipv4', $properties['ipv4']['format'] ?? null);
        $this->assertSame('ipv6', $properties['ipv6']['format'] ?? null);

        $this->assertSame([['format' => 'ipv4'], ['format' => 'ipv6']], $properties['ip']['oneOf'] ?? null);
    }
}
