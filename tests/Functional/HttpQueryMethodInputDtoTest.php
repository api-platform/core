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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\QueryInputDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Spike (RFC 10008 QUERY): validates that a Query operation can declare its criteria through an
 * input DTO whose properties carry #[QueryParameter], end-to-end.
 */
final class HttpQueryMethodInputDtoTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [QueryInputDummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([QueryInputDummy::class]);
        $this->createDummy('foo');
        $this->createDummy('bar');
    }

    private function createDummy(string $name): void
    {
        self::createClient()->request('POST', '/query_input_dummies', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['name' => $name],
        ]);
    }

    public function testCriteriaFromInputDtoFiltersTheCollection(): void
    {
        $response = self::createClient()->request('QUERY', '/query_input_dummies', [
            'headers' => ['Accept' => 'application/ld+json'],
            'json' => ['name' => 'foo'],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertSame(1, $data['hydra:totalItems']);
        $this->assertSame('foo', $data['hydra:member'][0]['name']);
    }

    public function testQueryInputDtoSchemaIsReferencedInOpenApi(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $json = $response->toArray();

        $query = $json['paths']['/query_input_dummies']['query'];
        $content = $query['requestBody']['content'];
        $this->assertArrayHasKey('application/x-www-form-urlencoded', $content);
        $this->assertArrayHasKey('application/json', $content);

        // The body schema is the input DTO schema, referencing the criteria object.
        $ref = $content['application/json']['schema']['$ref'] ?? null;
        $this->assertNotNull($ref, 'The QUERY request body must reference the input DTO schema.');
        $this->assertStringContainsString('QueryMethodCriteria', $ref);
    }
}
