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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\EnumClassParameter\EnumClassParameter;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class EnumClassParameterTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [EnumClassParameter::class];
    }

    #[DataProvider('provideValidValues')]
    public function testValidValueIsAccepted(string $query): void
    {
        self::createClient()->request('GET', '/enum_class_parameters?'.$query);

        $this->assertResponseIsSuccessful();
    }

    public static function provideValidValues(): \Generator
    {
        yield 'string backed enum' => ['status=draft'];
        yield 'int backed enum' => ['priority=2'];
    }

    #[DataProvider('provideInvalidValues')]
    public function testInvalidValueIsRejected(string $parameter, string $value): void
    {
        $response = self::createClient()->request('GET', '/enum_class_parameters?'.$parameter.'='.$value);

        $this->assertResponseStatusCodeSame(422);
        $this->assertArraySubset([
            'violations' => [
                ['propertyPath' => $parameter, 'message' => 'The value you selected is not a valid choice.'],
            ],
        ], $response->toArray(false));
    }

    public static function provideInvalidValues(): \Generator
    {
        yield 'unknown string case' => ['status', 'foo'];
        yield 'unknown int case' => ['priority', '3'];
        yield 'non numeric int value' => ['priority', 'foo'];
    }

    public function testOpenApiSchema(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();

        $parameters = array_column($response->toArray()['paths']['/enum_class_parameters']['get']['parameters'], null, 'name');

        $this->assertSame(['type' => 'string', 'enum' => ['draft', 'published']], $parameters['status']['schema']);
        $this->assertSame(['type' => 'integer', 'enum' => [1, 2]], $parameters['priority']['schema']);
    }
}
