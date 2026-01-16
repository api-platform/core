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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\TranslateValidationError;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\WithParameter;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

final class ParameterTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [WithParameter::class, TranslateValidationError::class];
    }

    public function testWithGroupFilter(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?groups[]=b');
        $this->assertArrayNotHasKey('a', $response->toArray());
        $this->assertArraySubset(['b' => 'bar'], $response->toArray());
        $response = self::createClient()->request('GET', 'with_parameters/1?groups[]=b&groups[]=a');
        $this->assertArraySubset(['a' => 'foo', 'b' => 'bar'], $response->toArray());
    }

    public function testWithGroupProvider(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?group[]=b&group[]=a');
        $this->assertArraySubset(['a' => 'foo', 'b' => 'bar'], $response->toArray());
    }

    public function testWithServiceFilter(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?properties[]=a');
        $this->assertArraySubset(['a' => 'foo'], $response->toArray());
    }

    public function testWithServiceProvider(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?service=blabla');
        $this->assertArrayNotHasKey('a', $response->toArray());
    }

    public function testWithObjectProvider(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters/1?service=blabla');
        $this->assertArrayNotHasKey('a', $response->toArray());
    }

    public function testWithHeader(): void
    {
        self::createClient()->request('GET', 'with_parameters/1?service=blabla', ['headers' => ['auth' => 'foo']]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDisabled(): void
    {
        self::createClient()->request('GET', 'with_disabled_parameter_validation');
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * Because of the openapiContext deprecation.
     * TODO: only select a few classes to generate the docs for a faster test.
     */
    #[\PHPUnit\Framework\Attributes\Group('legacy')]
    public function testDisableOpenApi(): void
    {
        $response = self::createClient()->request('GET', 'docs', ['headers' => ['accept' => 'application/vnd.openapi+json']]);
        $keys = [];
        foreach ($response->toArray(false)['paths']['/with_parameters/{id}']['get']['parameters'] as $parameter) {
            $keys[] = $parameter['name'];
        }

        $this->assertNotContains('array', $keys);
    }

    public function testHeaderAndQuery(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters_header_and_query?q=blabla', ['headers' => ['q' => '(complex stuff)']]);
        $this->assertEquals($response->toArray(), [
            '(complex stuff)',
            'blabla',
        ]);
    }

    public function testHeaderAndQueryWithArray(): void
    {
        $response = self::createClient()->request('GET', 'with_parameters_header_and_query?q[]=blabla', ['headers' => ['q' => '(complex stuff)']]);
        $this->assertEquals($response->toArray(), [
            '(complex stuff)',
            ['blabla'],
        ]);
    }

    public function testHeaderParameterRequired(): void
    {
        self::createClient()->request('GET', 'header_required', ['headers' => ['req' => 'blabla']]);
        $this->assertResponseStatusCodeSame(200);

        self::createClient()->request('GET', 'header_required', ['headers' => []]);
        $this->assertResponseStatusCodeSame(422);
    }

    #[DataProvider('provideHeaderValues')]
    public function testHeaderParameter(string $url, array $headers, int $expectedStatusCode): void
    {
        self::createClient()->request('GET', $url, ['headers' => $headers]);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public static function provideHeaderValues(): iterable
    {
        // header_integer
        yield 'missing header header_integer' => ['header_integer', [], 200];
        yield 'valid integer header_integer' => ['header_integer', ['Foo' => '3'], 200];
        yield 'too high header_integer' => ['header_integer', ['Foo' => '6'], 422];
        yield 'too low header_integer' => ['header_integer', ['Foo' => '0'], 422];
        yield 'invalid integer header_integer' => ['header_integer', ['Foo' => 'string'], 422];

        // header_float
        yield 'missing header header_float' => ['header_float', [], 200];
        yield 'valid float header_float' => ['header_float', ['Bar' => '3.5'], 200];
        yield 'valid integer header_float' => ['header_float', ['Bar' => '3'], 200];
        yield 'too high header_float' => ['header_float', ['Bar' => '600'], 422];
        yield 'too low header_float' => ['header_float', ['Bar' => '0'], 422];
        yield 'invalid number header_float' => ['header_float', ['Bar' => 'string'], 422];

        // header_boolean
        yield 'missing header header_boolean' => ['header_boolean', [], 200];
        yield 'valid boolean false header_boolean' => ['header_boolean', ['Lorem' => 'false'], 200];
        yield 'valid boolean true header_boolean' => ['header_boolean', ['Lorem' => 'true'], 200];
        yield 'valid boolean 0 header_boolean' => ['header_boolean', ['Lorem' => 0], 200];
        yield 'valid boolean 0 string header_boolean' => ['header_boolean', ['Lorem' => '0'], 200];
        yield 'valid boolean 1 header_boolean' => ['header_boolean', ['Lorem' => 1], 200];
        yield 'valid boolean 1 string header_boolean' => ['header_boolean', ['Lorem' => '1'], 200];
        yield 'invalid boolean header_boolean' => ['header_boolean', ['Lorem' => 'string'], 422];

        // query_uuid
        yield 'valid uuid header_uuid' => ['header_uuid', ['uuid' => '216fff40-98d9-11e3-a5e2-0800200c9a66'], 200];
        yield 'invalid uuid header_uuid' => ['header_uuid', ['uuid' => 'invalid_uuid'], 422];

        // query_ulid
        yield 'valid ulid header_ulid' => ['header_ulid', ['ulid' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'], 200];
        yield 'invalid ulid header_ulid' => ['header_ulid', ['ulid' => 'invalid_ulid'], 422];
    }

    #[DataProvider('provideQueryValues')]
    public function testQueryParameter(string $url, array $query, int $expectedStatusCode): void
    {
        self::createClient()->request('GET', $url, ['query' => $query]);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public static function provideQueryValues(): iterable
    {
        // query_integer
        yield 'valid integer query_integer' => ['query_integer', ['Foo' => '3'], 200];
        yield 'too high query_integer' => ['query_integer', ['Foo' => '6'], 422];
        yield 'too low query_integer' => ['query_integer', ['Foo' => '0'], 422];
        yield 'invalid integer query_integer' => ['query_integer', ['Foo' => 'string'], 422];

        // query_float
        yield 'valid float query_float' => ['query_float', ['Bar' => '3.5'], 200];
        yield 'valid integer query_float' => ['query_float', ['Bar' => '3'], 200];
        yield 'too high query_float' => ['query_float', ['Bar' => '600'], 422];
        yield 'too low query_float' => ['query_float', ['Bar' => '0'], 422];
        yield 'invalid number query_float' => ['query_float', ['Bar' => 'string'], 422];

        // query_boolean
        yield 'valid boolean false query_boolean' => ['query_boolean', ['Lorem' => false], 200];
        yield 'valid boolean false string query_boolean' => ['query_boolean', ['Lorem' => 'false'], 200];
        yield 'valid boolean true query_boolean' => ['query_boolean', ['Lorem' => true], 200];
        yield 'valid boolean true string query_boolean' => ['query_boolean', ['Lorem' => 'true'], 200];
        yield 'valid boolean 0 query_boolean' => ['query_boolean', ['Lorem' => 0], 200];
        yield 'valid boolean 0 string query_boolean' => ['query_boolean', ['Lorem' => '0'], 200];
        yield 'valid boolean 1 query_boolean' => ['query_boolean', ['Lorem' => 1], 200];
        yield 'valid boolean 1 string query_boolean' => ['query_boolean', ['Lorem' => '1'], 200];
        yield 'invalid boolean query_boolean' => ['query_boolean', ['Lorem' => 'string'], 422];

        // query_uuid
        yield 'valid uuid query_uuid' => ['query_uuid', ['uuid' => '216fff40-98d9-11e3-a5e2-0800200c9a66'], 200];
        yield 'invalid uuid query_uuid' => ['query_uuid', ['uuid' => 'invalid_uuid'], 422];

        // query_ulid
        yield 'valid ulid query_ulid' => ['query_ulid', ['ulid' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'], 200];
        yield 'invalid ulid query_ulid' => ['query_ulid', ['ulid' => 'invalid_ulid'], 422];
    }

    #[DataProvider('provideCountryValues')]
    public function testIssue7157(string $queryParameter, int $expectedStatusCode): void
    {
        self::createClient()->request('GET', 'with_parameters_country?'.$queryParameter);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public static function provideCountryValues(): iterable
    {
        yield 'valid country' => ['country=FR', 200];
        yield 'array of countries' => ['country[]=FR', 422];
    }

    #[DataProvider('provideCountriesValues')]
    public function testIssue7157WithCountries(string $queryParameter, int $expectedStatusCode): void
    {
        self::createClient()->request('GET', 'with_parameters_countries?'.$queryParameter);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public static function provideCountriesValues(): iterable
    {
        yield 'valid country' => ['country=FR', 200];
        yield 'array of countries' => ['country[]=FR', 200];
    }

    public function testDefaultValues(): void
    {
        self::createClient()->request('GET', 'parameter_defaults');
        $this->assertResponseIsSuccessful();
    }
}
