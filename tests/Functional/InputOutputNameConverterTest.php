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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DummyDtoNameConverted;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Tests that API Platform's name converter is applied to input and output DTOs.
 *
 * Reproduces and validates the fix for issue #7705:
 * https://github.com/api-platform/core/issues/7705
 *
 * After PR #7691 isolated AP's name converter from Symfony's global serializer,
 * input/output DTOs stopped getting the AP name converter because
 * supportsDenormalization/supportsNormalization rejected them on re-entry
 * (the input/output context keys were unset before re-entering the serializer).
 */
final class InputOutputNameConverterTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyDtoNameConverted::class];
    }

    public function testInputDtoNameConverterIsApplied(): void
    {
        $response = self::createClient()->request('POST', '/dummy_dto_name_converted', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name_converted' => 'converted'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('converted', $data['name_converted']);
    }

    public function testOutputDtoNameConverterIsApplied(): void
    {
        $response = self::createClient()->request('GET', '/dummy_dto_name_converted/1');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('name_converted', $data);
        $this->assertSame('converted', $data['name_converted']);
    }

    /**
     * Reproduces the controller use case from issue #7705:
     * when a custom controller deserializes the input DTO via SerializerInterface,
     * the API Platform name converter must still be applied.
     *
     * @see https://github.com/api-platform/core/issues/7705
     */
    public function testInputDtoNameConverterIsAppliedWithController(): void
    {
        $response = self::createClient()->request('POST', '/dummy_dto_name_converted_controller', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name_converted' => 'converted'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray(false);
        $this->assertSame('converted', $data['nameConverted']);
    }
}
