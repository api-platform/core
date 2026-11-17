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

namespace ApiPlatform\Laravel\Tests;

use ApiPlatform\Laravel\Test\ApiTestAssertionsTrait;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class EnumClassParameterTest extends TestCase
{
    use ApiTestAssertionsTrait;
    use WithWorkbench;

    #[DataProvider('provideValidValues')]
    public function testValidValueIsAccepted(string $query): void
    {
        $response = $this->get('/api/with_enum_class_parameters?'.$query, ['Accept' => 'application/ld+json']);

        $response->assertStatus(200);
    }

    public static function provideValidValues(): \Generator
    {
        yield 'string backed enum' => ['status=open'];
        yield 'int backed enum' => ['priority=2'];
    }

    #[DataProvider('provideInvalidValues')]
    public function testInvalidValueIsRejected(string $query): void
    {
        $response = $this->get('/api/with_enum_class_parameters?'.$query, ['Accept' => 'application/ld+json']);

        $response->assertStatus(422);
    }

    public static function provideInvalidValues(): \Generator
    {
        yield 'unknown string case' => ['status=foo'];
        yield 'unknown int case' => ['priority=3'];
        yield 'non numeric int value' => ['priority=foo'];
    }

    public function testOpenApiSchema(): void
    {
        $response = $this->get('/api/docs', ['Accept' => 'application/vnd.openapi+json']);
        $response->assertStatus(200);

        $parameters = array_column($response->json('paths')['/api/with_enum_class_parameters']['get']['parameters'], null, 'name');

        $this->assertSame(['type' => 'string', 'enum' => ['open', 'closed']], $parameters['status']['schema']);
        $this->assertSame(['type' => 'integer', 'enum' => [1, 2]], $parameters['priority']['schema']);
    }
}
