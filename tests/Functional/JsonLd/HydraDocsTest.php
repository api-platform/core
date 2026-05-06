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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\HydraDocsResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class HydraDocsTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [HydraDocsResource::class];
    }

    public function testDocumentationLinkHeader(): void
    {
        $response = self::createClient()->request('GET', '/');
        $link = $response->getHeaders()['link'][0] ?? '';
        $this->assertStringContainsString('rel="http://www.w3.org/ns/hydra/core#apiDocumentation"', $link);
    }

    public function testApiVocabularyShape(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonld');
        $this->assertResponseIsSuccessful();
        $this->assertSame('application/ld+json; charset=utf-8', $response->getHeaders()['content-type'][0]);
        $body = $response->toArray();

        $this->assertIsArray($body['@context']);
        $vocab = $body['@context'][1] ?? null;
        $this->assertIsArray($vocab);
        $this->assertSame('http://localhost/docs.jsonld#', $vocab['@vocab']);
        $this->assertSame(['@id' => 'rdfs:domain', '@type' => '@id'], $vocab['domain']);
        $this->assertSame(['@id' => 'rdfs:range', '@type' => '@id'], $vocab['range']);
        $this->assertSame(['@id' => 'rdfs:subClassOf', '@type' => '@id'], $vocab['subClassOf']);

        $this->assertSame('/docs.jsonld', $body['@id']);
        $this->assertNotEmpty($body['hydra:title']);
        $this->assertNotEmpty($body['hydra:description']);
        $this->assertSame('/', $body['hydra:entrypoint']);
    }

    public function testSupportedClassesIncludeRegisteredResource(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonld');
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $titles = array_column($body['hydra:supportedClass'], 'hydra:title');
        $this->assertContains('Entrypoint', $titles);
        $this->assertContains('JsonLdHydraDocs', $titles);
    }

    public function testSupportedClassDescribesPropertiesAndOperations(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonld');
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();

        $resource = $this->findClass($body['hydra:supportedClass'], 'JsonLdHydraDocs');
        $this->assertNotNull($resource, 'JsonLdHydraDocs must appear in hydra:supportedClass');
        $this->assertSame('hydra:Class', $resource['@type']);
        $this->assertSame('A docs sample.', $resource['hydra:description']);

        $name = $this->findProperty($resource, 'name');
        $this->assertNotNull($name);
        $this->assertSame('hydra:SupportedProperty', $name['@type']);
        $this->assertTrue($name['hydra:readable']);
        $this->assertSame('https://schema.org/name', $name['hydra:property']['@id']);
        $this->assertSame('rdf:Property', $name['hydra:property']['@type']);

        $get = $this->findOperation($resource, 'GET');
        $this->assertNotNull($get);
        $this->assertContains('hydra:Operation', (array) $get['@type']);
        $this->assertSame('GET', $get['hydra:method']);
        $this->assertSame('JsonLdHydraDocs', $get['returns']);

        $delete = $this->findOperation($resource, 'DELETE');
        $this->assertNotNull($delete);
        $this->assertSame('owl:Nothing', $delete['returns']);
    }

    public function testDeprecatedFlagSurvivesOnResource(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonld');
        $this->assertResponseIsSuccessful();
        $body = $response->toArray();
        $resource = $this->findClass($body['hydra:supportedClass'], 'JsonLdHydraDocs');
        $deprecatedField = $this->findProperty($resource, 'deprecatedField');
        $this->assertNotNull($deprecatedField);
        $this->assertTrue($deprecatedField['hydra:property']['owl:deprecated']);
    }

    /**
     * @param list<array<string, mixed>> $supportedClass
     *
     * @return array<string, mixed>|null
     */
    private function findClass(array $supportedClass, string $title): ?array
    {
        foreach ($supportedClass as $cls) {
            if (($cls['hydra:title'] ?? null) === $title) {
                return $cls;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $resource
     *
     * @return array<string, mixed>|null
     */
    private function findProperty(array $resource, string $name): ?array
    {
        foreach ($resource['hydra:supportedProperty'] ?? [] as $prop) {
            if (($prop['hydra:title'] ?? null) === $name) {
                return $prop;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $resource
     *
     * @return array<string, mixed>|null
     */
    private function findOperation(array $resource, string $method): ?array
    {
        foreach ($resource['hydra:supportedOperation'] ?? [] as $op) {
            if (($op['hydra:method'] ?? null) === $method) {
                return $op;
            }
        }

        return null;
    }
}
