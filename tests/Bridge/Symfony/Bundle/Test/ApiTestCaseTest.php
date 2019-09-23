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

namespace ApiPlatform\Core\Tests\Bridge\Symfony\Bundle\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Model\ResourceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Runner\Version;

class ApiTestCaseTest extends ApiTestCase
{
    public function testAssertJsonContains(): void
    {
        if (version_compare(Version::id(), '8.0.0', '<')) {
            $this->markTestSkipped('Requires PHPUnit 8');
        }

        self::createClient()->request('GET', '/');
        $this->assertJsonContains(['@context' => '/contexts/Entrypoint']);
    }

    public function testAssertJsonContainsWithJsonObjectString(): void
    {
        if (version_compare(Version::id(), '8.0.0', '<')) {
            $this->markTestSkipped('Requires PHPUnit 8');
        }

        self::createClient()->request('GET', '/');
        $this->assertJsonContains(<<<JSON
{
    "@context": "/contexts/Entrypoint"
}
JSON
        );
    }

    public function testAssertJsonContainsWithJsonScalarString(): void
    {
        if (version_compare(Version::id(), '8.0.0', '<')) {
            $this->markTestSkipped('Requires PHPUnit 8');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$subset must be array or string (JSON array or JSON object)');

        self::createClient()->request('GET', '/');
        $this->assertJsonContains(<<<JSON
"/contexts/Entrypoint"
JSON
        );
    }

    public function testAssertJsonEquals(): void
    {
        self::createClient()->request('GET', '/contexts/Address');
        $this->assertJsonEquals([
            '@context' => [
                '@vocab' => 'http://example.com/docs.jsonld#',
                'hydra' => 'http://www.w3.org/ns/hydra/core#',
                'name' => 'Address/name',
            ],
        ]);
    }

    public function testAssertJsonEqualsWithJsonObjectString(): void
    {
        self::createClient()->request('GET', '/contexts/Address');
        $this->assertJsonEquals(<<<JSON
{
    "@context": {
        "@vocab": "http://example.com/docs.jsonld#",
        "hydra": "http://www.w3.org/ns/hydra/core#",
        "name": "Address/name"
    }
}
JSON
        );
    }

    public function testAssertJsonEqualsWithJsonScalarString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$json must be array or string (JSON array or JSON object)');

        self::createClient()->request('GET', '/contexts/Address');
        $this->assertJsonEquals(<<<JSON
"Address/name"
JSON
        );
    }

    public function testAssertMatchesJsonSchema(): void
    {
        $jsonSchema = <<<JSON
{
  "type": "object",
  "properties": {
    "@context": {"pattern": "^/contexts/Entrypoint"},
    "@id": {"pattern": "^/$"},
    "@type": {"pattern": "^Entrypoint$"},
    "dummy": {}
  },
  "additionalProperties": true,
  "required": ["@context", "@id", "@type", "dummy"]
}
JSON;

        self::createClient()->request('GET', '/');
        $this->assertMatchesJsonSchema($jsonSchema);
        $this->assertMatchesJsonSchema(json_decode($jsonSchema, true));
    }

    public function testAssertMatchesResourceCollectionJsonSchema(): void
    {
        self::createClient()->request('GET', '/resource_interfaces');
        $this->assertMatchesResourceCollectionJsonSchema(ResourceInterface::class);
    }

    public function testAssertMatchesResourceItemJsonSchema(): void
    {
        self::createClient()->request('GET', '/resource_interfaces/some-id');
        $this->assertMatchesResourceItemJsonSchema(ResourceInterface::class);
    }

    // Next tests have been imported from dms/phpunit-arraysubset-asserts, because the original constraint has been deprecated.

    public function testAssertArraySubsetPassesStrictConfig(): void
    {
        if (version_compare(Version::id(), '8.0.0', '<')) {
            $this->markTestSkipped('Requires PHPUnit 8');
        }

        $this->expectException(ExpectationFailedException::class);
        $this->assertArraySubset(['bar' => 0], ['bar' => '0'], true);
    }

    public function testAssertArraySubsetDoesNothingForValidScenario(): void
    {
        if (version_compare(Version::id(), '8.0.0', '<')) {
            $this->markTestSkipped('Requires PHPUnit 8');
        }

        $this->assertArraySubset([1, 2], [1, 2, 3]);
    }

    public function testFindIriBy(): void
    {
        self::bootKernel();
        /**
         * @var EntityManagerInterface
         */
        $manager = self::$container->get('doctrine')->getManager();
        $classes = $manager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($manager);

        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        self::createClient()->request('POST', '/dummies', [
            'headers' => [
                'content-type' => 'application/json',
                'accept' => 'text/xml',
            ],
            'body' => '{"name": "Kevin"}',
        ]);
        $this->assertResponseIsSuccessful();

        $resource = 'mongodb' === self::$container->getParameter('kernel.environment') ? DummyDocument::class : Dummy::class;
        $this->assertRegExp('~^/dummies/\d+~', self::findIriBy($resource, ['name' => 'Kevin']));
        $this->assertNull(self::findIriBy($resource, ['name' => 'not-exist']));
    }
}
