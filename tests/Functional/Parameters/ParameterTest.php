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
        return [WithParameter::class];
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
    public function testHeaderParameterInteger(string $value, int $expectedStatusCode): void
    {
        self::createClient()->request('GET', 'header_integer', ['headers' => ['Foo' => $value]]);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public static function provideHeaderValues(): iterable
    {
        yield 'valid integer' => ['3', 200];
        yield 'too high' => ['6', 422];
        yield 'too low' => ['0', 422];
        yield 'invalid integer' => ['string', 422];
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
}
