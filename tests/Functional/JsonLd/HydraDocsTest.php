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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\HydraDocsDeprecated;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\HydraDocsRelated;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\JsonLd\HydraDocsResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class HydraDocsTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [HydraDocsResource::class, HydraDocsRelated::class, HydraDocsDeprecated::class];
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

    public function testSupportedClassesIncludeRegisteredAndOmitNonResources(): void
    {
        $response = self::createClient()->request('GET', '/docs.jsonld');
        $body = $response->toArray();
        $titles = array_column($body['hydra:supportedClass'], 'hydra:title');
        $this->assertContains('Entrypoint', $titles);
        $this->assertContains('JsonLdHydraDocs', $titles);
        $this->assertContains('JsonLdHydraDocsRelated', $titles);
        $this->assertNotContains('UnknownDummy', $titles);
        $this->assertNotContains('HydraDocsResource', $titles, 'class FQCN should not leak when shortName is set');
    }

    public function testResourceClassMetadata(): void
    {
        $body = self::createClient()->request('GET', '/docs.jsonld')->toArray();
        $resource = $this->findClass($body['hydra:supportedClass'], 'JsonLdHydraDocs');
        $this->assertNotNull($resource);
        $this->assertSame('#JsonLdHydraDocs', $resource['@id']);
        $this->assertSame('hydra:Class', $resource['@type']);
        $this->assertSame('JsonLdHydraDocs', $resource['hydra:title']);
        $this->assertSame('A docs sample.', $resource['hydra:description']);
    }

    public function testSubClassOfFromTypes(): void
    {
        $body = self::createClient()->request('GET', '/docs.jsonld')->toArray();
        $related = $this->findClass($body['hydra:supportedClass'], 'JsonLdHydraDocsRelated');
        $this->assertNotNull($related);
        $this->assertSame('https://schema.org/Product', $related['subClassOf']);
    }

    public function testPropertyMetadataReadableWritableRequired(): void
    {
        $body = self::createClient()->request('GET', '/docs.jsonld')->toArray();
        $resource = $this->findClass($body['hydra:supportedClass'], 'JsonLdHydraDocs');
        $name = $this->findProperty($resource, 'name');
        $this->assertNotNull($name);
        $this->assertSame('hydra:SupportedProperty', $name['@type']);
        $this->assertTrue($name['hydra:readable']);
        $this->assertSame('https://schema.org/name', $name['hydra:property']['@id']);
        $this->assertSame('rdf:Property', $name['hydra:property']['@type']);
        $this->assertSame('name', $name['hydra:property']['label']);
        $this->assertSame('#JsonLdHydraDocs', $name['hydra:property']['domain']);
        $this->assertSame('xsd:string', $name['hydra:property']['range']);
        $this->assertSame('name', $name['hydra:title']);
        $this->assertSame('The doc resource name.', $name['hydra:description']);
    }

    public function testRelationPropertyRangeAndCardinality(): void
    {
        $body = self::createClient()->request('GET', '/docs.jsonld')->toArray();
        $resource = $this->findClass($body['hydra:supportedClass'], 'JsonLdHydraDocs');

        $related = $this->findProperty($resource, 'related');
        $this->assertNotNull($related);
        $this->assertSame('#JsonLdHydraDocsRelated', $related['hydra:property']['range']);
        $this->assertSame(1, $related['hydra:property']['owl:maxCardinality']);

        $relateds = $this->findProperty($resource, 'relateds');
        $this->assertNotNull($relateds);
        $this->assertSame('#JsonLdHydraDocsRelated', $relateds['hydra:property']['range']);
        $this->assertArrayNotHasKey('owl:maxCardinality', $relateds['hydra:property']);
    }

    public function testOperationMetadata(): void
    {
        $body = self::createClient()->request('GET', '/docs.jsonld')->toArray();
        $resource = $this->findClass($body['hydra:supportedClass'], 'JsonLdHydraDocs');

        $get = $this->findOperation($resource, 'GET');
        $this->assertNotNull($get);
        $this->assertContains('hydra:Operation', (array) $get['@type']);
        $this->assertContains('schema:FindAction', (array) $get['@type']);
        $this->assertSame('GET', $get['hydra:method']);
        $this->assertSame('getJsonLdHydraDocs', $get['hydra:title']);
        $this->assertSame('Retrieves a JsonLdHydraDocs resource.', $get['hydra:description']);
        $this->assertSame('JsonLdHydraDocs', $get['returns']);

        $put = $this->findOperation($resource, 'PUT');
        $this->assertNotNull($put);
        $this->assertSame('putJsonLdHydraDocs', $put['hydra:title']);
        $this->assertSame('Replaces the JsonLdHydraDocs resource.', $put['hydra:description']);

        $delete = $this->findOperation($resource, 'DELETE');
        $this->assertNotNull($delete);
        $this->assertSame('deleteJsonLdHydraDocs', $delete['hydra:title']);
        $this->assertSame('Deletes the JsonLdHydraDocs resource.', $delete['hydra:description']);
        $this->assertSame('owl:Nothing', $delete['returns']);
    }

    public function testDeprecationOnResourceAndProperty(): void
    {
        $body = self::createClient()->request('GET', '/docs.jsonld')->toArray();
        $deprecated = $this->findClass($body['hydra:supportedClass'], 'JsonLdHydraDocsDeprecated');
        $this->assertNotNull($deprecated);
        $this->assertTrue($deprecated['owl:deprecated']);

        $deprecatedField = $this->findProperty($deprecated, 'deprecatedField');
        $this->assertNotNull($deprecatedField);
        $this->assertTrue($deprecatedField['hydra:property']['owl:deprecated']);

        $entrypoint = $this->findClass($body['hydra:supportedClass'], 'Entrypoint');
        $this->assertNotNull($entrypoint);
        $deprecatedEntrypointProp = $this->findProperty($entrypoint, 'getJsonLdHydraDocsDeprecatedCollection');
        $this->assertNotNull($deprecatedEntrypointProp, 'deprecation on resource must propagate to entrypoint property');
        $this->assertTrue($deprecatedEntrypointProp['owl:deprecated']);
    }

    /**
     * @param list<array<string, mixed>> $supportedClass
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
