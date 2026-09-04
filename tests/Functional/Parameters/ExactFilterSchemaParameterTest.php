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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\ExactFilterSchemaParameter\ExactFilterSchemaParameter;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class ExactFilterSchemaParameterTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ExactFilterSchemaParameter::class];
    }

    /**
     * @return array<string, array{name: string, schema: array<string, mixed>}>
     */
    private function getOpenApiParametersByName(): array
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $openApiDoc = $response->toArray();

        $parameters = $openApiDoc['paths']['/exact_filter_schema_parameters']['get']['parameters'];

        $byName = [];
        foreach ($parameters as $parameter) {
            $byName[$parameter['name']] = $parameter;
        }

        return $byName;
    }

    public function testScalarSchemaWithDefaultCastToArrayExposesBothVariants(): void
    {
        $parameters = $this->getOpenApiParametersByName();

        $this->assertArrayHasKey('code', $parameters);
        $this->assertArrayHasKey('code[]', $parameters);

        $this->assertSame('integer', $parameters['code']['schema']['type']);

        $this->assertSame('array', $parameters['code[]']['schema']['type']);
        $this->assertSame('integer', $parameters['code[]']['schema']['items']['type']);
    }

    public function testScalarSchemaWithCastToArrayTrueExposesArrayVariantOnly(): void
    {
        $parameters = $this->getOpenApiParametersByName();

        $this->assertArrayNotHasKey('codeArrayCast', $parameters);
        $this->assertArrayHasKey('codeArrayCast[]', $parameters);

        $this->assertSame('array', $parameters['codeArrayCast[]']['schema']['type']);
        $this->assertSame('integer', $parameters['codeArrayCast[]']['schema']['items']['type']);
    }

    public function testScalarSchemaWithCastToArrayFalseExposesSingularVariantOnly(): void
    {
        $parameters = $this->getOpenApiParametersByName();

        $this->assertArrayHasKey('codeNoArray', $parameters);
        $this->assertArrayNotHasKey('codeNoArray[]', $parameters);

        $this->assertSame('integer', $parameters['codeNoArray']['schema']['type']);
    }

    public function testArraySchemaWithCastToArrayTrueExposesSingleArrayVariant(): void
    {
        $parameters = $this->getOpenApiParametersByName();

        $this->assertArrayNotHasKey('codeExplicitArray', $parameters);
        $this->assertArrayHasKey('codeExplicitArray[]', $parameters);

        $this->assertSame('array', $parameters['codeExplicitArray[]']['schema']['type']);
        $this->assertSame('integer', $parameters['codeExplicitArray[]']['schema']['items']['type']);
    }
}
