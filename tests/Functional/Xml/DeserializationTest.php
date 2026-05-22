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

namespace ApiPlatform\Tests\Functional\Xml;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyProperty;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ResourceWithBoolean;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ResourceWithFloat;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ResourceWithInteger;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ResourceWithString;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class DeserializationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [
            ResourceWithString::class,
            ResourceWithBoolean::class,
            ResourceWithInteger::class,
            ResourceWithFloat::class,
            DummyProperty::class,
        ];
    }

    private const XML_HEADERS = [
        'Accept' => 'application/xml',
        'Content-Type' => 'application/xml',
    ];

    public function testPostStringResource(): void
    {
        $this->recreateSchema([ResourceWithString::class]);

        self::createClient()->request('POST', '/resource_with_strings', [
            'headers' => self::XML_HEADERS,
            'body' => <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <ResourceWithString>
                  <myStringField>string</myStringField>
                </ResourceWithString>
                XML,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
    }

    public static function booleanValues(): iterable
    {
        yield ['true'];
        yield ['false'];
        yield ['1'];
        yield ['0'];
    }

    #[DataProvider('booleanValues')]
    public function testPostBooleanResource(string $value): void
    {
        $this->recreateSchema([ResourceWithBoolean::class]);

        self::createClient()->request('POST', '/resource_with_booleans', [
            'headers' => self::XML_HEADERS,
            'body' => \sprintf(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <ResourceWithBoolean>
                  <myBooleanField>%s</myBooleanField>
                </ResourceWithBoolean>
                XML, $value),
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
    }

    public static function integerValues(): iterable
    {
        yield ['42'];
        yield ['-6'];
        yield ['1'];
        yield ['0'];
    }

    #[DataProvider('integerValues')]
    public function testPostIntegerResource(string $value): void
    {
        $this->recreateSchema([ResourceWithInteger::class]);

        self::createClient()->request('POST', '/resource_with_integers', [
            'headers' => self::XML_HEADERS,
            'body' => \sprintf(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <ResourceWithInteger>
                  <myIntegerField>%s</myIntegerField>
                </ResourceWithInteger>
                XML, $value),
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
    }

    public static function floatValues(): iterable
    {
        yield ['3.14'];
        yield ['NaN'];
        yield ['INF'];
        yield ['-INF'];
    }

    #[DataProvider('floatValues')]
    public function testPostFloatResource(string $value): void
    {
        if ($this->isMysql()) {
            $this->markTestSkipped('MySQL does not support NaN/Inf floats');
        }

        $this->recreateSchema([ResourceWithFloat::class]);

        self::createClient()->request('POST', '/resource_with_floats', [
            'headers' => self::XML_HEADERS,
            'body' => \sprintf(<<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <ResourceWithFloat>
                  <myFloatField>%s</myFloatField>
                </ResourceWithFloat>
                XML, $value),
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
    }

    public function testPostSingleElementCollection(): void
    {
        $this->recreateSchema([DummyProperty::class]);

        self::createClient()->request('POST', '/dummy_properties', [
            'headers' => self::XML_HEADERS,
            'body' => <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <DummyProperty>
                  <groups>
                    <DummyGroup>
                      <foo>bar</foo>
                    </DummyGroup>
                  </groups>
                </DummyProperty>
                XML,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=utf-8');
    }
}
