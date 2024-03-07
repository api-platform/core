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

namespace ApiPlatform\Tests\Symfony\Bundle\Test;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoInputOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6041\NumericValidated;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6146\Issue6146Child;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6146\Issue6146Parent;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\JsonSchemaContextDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\User;
use ApiPlatform\Tests\Fixtures\TestBundle\Model\ResourceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class ApiTestCaseTest extends ApiTestCase
{
    use ExpectDeprecationTrait;

    public function testAssertJsonContains(): void
    {
        self::createClient()->request('GET', '/');
        $this->assertJsonContains(['@context' => '/contexts/Entrypoint']);
    }

    public function testAssertJsonContainsWithJsonObjectString(): void
    {
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
                '@vocab' => 'http://localhost/docs.jsonld#',
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
        "@vocab": "http://localhost/docs.jsonld#",
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

    public function testAssertMatchesResourceCollectionJsonSchemaKeepSerializationContext(): void
    {
        $this->recreateSchema();

        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();

        $parent = new Issue6146Parent();
        $manager->persist($parent);

        $child = new Issue6146Child();
        $child->setParent($parent);
        $parent->addChild($child);
        $manager->persist($child);

        $manager->persist($child);
        $manager->flush();

        self::createClient()->request('GET', "issue-6146-parents/{$parent->getId()}");
        $this->assertMatchesResourceItemJsonSchema(Issue6146Parent::class);

        self::createClient()->request('GET', '/issue-6146-parents');
        $this->assertMatchesResourceCollectionJsonSchema(Issue6146Parent::class);
    }

    public function testAssertMatchesResourceItemJsonSchema(): void
    {
        self::createClient()->request('GET', '/resource_interfaces/some-id');
        $this->assertMatchesResourceItemJsonSchema(ResourceInterface::class);
    }

    public function testAssertMatchesResourceItemJsonSchemaWithCustomJson(): void
    {
        $this->recreateSchema();

        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        $jsonSchemaContextDummy = new JsonSchemaContextDummy();
        $manager->persist($jsonSchemaContextDummy);
        $manager->flush();

        self::createClient()->request('GET', '/json_schema_context_dummies/1');
        $this->assertMatchesResourceItemJsonSchema(JsonSchemaContextDummy::class);
    }

    public function testAssertMatchesResourceItemJsonSchemaOutput(): void
    {
        $this->recreateSchema();

        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        $dummyDtoInputOutput = new DummyDtoInputOutput();
        $dummyDtoInputOutput->str = 'lorem';
        $dummyDtoInputOutput->num = 54;
        $manager->persist($dummyDtoInputOutput);
        $manager->flush();
        self::createClient()->request('GET', '/dummy_dto_input_outputs/1');
        $this->assertMatchesResourceItemJsonSchema(DummyDtoInputOutput::class);
    }

    public function testAssertMatchesResourceItemAndCollectionJsonSchemaOutputWithContext(): void
    {
        $this->recreateSchema();

        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        $user = new User();
        $user->setFullname('Grégoire');
        $user->setPlainPassword('password');

        $manager->persist($user);
        $manager->flush();

        self::createClient()->request('GET', "/users-with-groups/{$user->getId()}");
        $this->assertMatchesResourceItemJsonSchema(User::class, null, 'jsonld', ['groups' => ['api-test-case-group']]);

        self::createClient()->request('GET', '/users-with-groups');
        $this->assertMatchesResourceCollectionJsonSchema(User::class, null, 'jsonld', ['groups' => ['api-test-case-group']]);
    }

    public function testAssertMatchesResourceItemAndCollectionJsonSchemaOutputWithRangeAssertions(): void
    {
        $this->recreateSchema();

        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        $numericValidated = new NumericValidated();
        $numericValidated->range = 5;
        $numericValidated->greaterThanMe = 11;
        $numericValidated->greaterThanOrEqualToMe = 10.99;
        $numericValidated->lessThanMe = 11;
        $numericValidated->lessThanOrEqualToMe = 99.33;
        $numericValidated->positive = 1;
        $numericValidated->positiveOrZero = 0;
        $numericValidated->negative = -1;
        $numericValidated->negativeOrZero = 0;

        $manager->persist($numericValidated);
        $manager->flush();

        self::createClient()->request('GET', "/numeric-validated/{$numericValidated->getId()}");
        $this->assertMatchesResourceItemJsonSchema(NumericValidated::class);

        self::createClient()->request('GET', '/numeric-validated');
        $this->assertMatchesResourceCollectionJsonSchema(NumericValidated::class);
    }

    // Next tests have been imported from dms/phpunit-arraysubset-asserts, because the original constraint has been deprecated.

    public function testAssertArraySubsetPassesStrictConfig(): void
    {
        $this->expectException(ExpectationFailedException::class);
        $this->assertArraySubset(['bar' => 0], ['bar' => '0'], true);
    }

    public function testAssertArraySubsetDoesNothingForValidScenario(): void
    {
        $this->assertArraySubset([1, 2], [1, 2, 3]);
    }

    public function testFindIriBy(): void
    {
        $this->recreateSchema();

        self::createClient()->request('POST', '/dummies', [
            'headers' => [
                'content-type' => 'application/json',
                'accept' => 'text/xml',
            ],
            'body' => '{"name": "Kevin"}',
        ]);
        $this->assertResponseIsSuccessful();

        $container = static::getContainer();
        $resource = 'mongodb' === $container->getParameter('kernel.environment') ? DummyDocument::class : Dummy::class;
        $this->assertMatchesRegularExpression('~^/dummies/\d+~', self::findIriBy($resource, ['name' => 'Kevin']));
        $this->assertNull(self::findIriBy($resource, ['name' => 'not-exist']));
    }

    public function testGetPrioritizedOperation(): void
    {
        $r = self::createClient()->request('GET', '/operation_priority/1', [
            'headers' => [
                'accept' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseIsSuccessful();
    }

    /**
     * @group mercure
     */
    public function testGetMercureMessages(): void
    {
        // debug mode is required to get Mercure TraceableHub
        $this->recreateSchema(['debug' => true, 'environment' => 'mercure']);

        self::createClient()->request('POST', '/direct_mercures', [
            'headers' => [
                'content-type' => 'application/ld+json',
                'accept' => 'application/ld+json',
            ],
            'body' => '{"name": "Hello World!"}',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, self::getMercureMessages());
        self::assertMercureUpdateMatchesJsonSchema(
            update: self::getMercureMessage(),
            topics: ['http://localhost/direct_mercures/1'],
            jsonSchema: <<<JSON
{
    "\$schema": "https:\/\/json-schema.org\/draft-07\/schema#",
    "type": "object",
    "additionalProperties": false,
    "properties": {
        "@context": {
            "readOnly": true,
            "type": "string",
            "pattern": "^/contexts/DirectMercure$"
        },
        "@id": {
            "readOnly": true,
            "type": "string",
            "pattern": "^/direct_mercures/.+$"
        },
        "@type": {
            "readOnly": true,
            "type": "string"
        },
        "id": {
            "type": "number"
        },
        "name": {
            "type": "string"
        }
    },
    "required": [
        "@context",
        "@id",
        "@type",
        "id",
        "name"
    ]
}
JSON
        );
    }

    private function recreateSchema(array $options = []): void
    {
        self::bootKernel($options);

        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        /** @var ClassMetadata[] $classes */
        $classes = $manager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($manager);

        @$schemaTool->dropSchema($classes);
        @$schemaTool->createSchema($classes);
    }

    /**
     * @group legacy
     */
    public function testExceptionNormalizer(): void
    {
        $response = self::createClient()->request('GET', '/issue5921', [
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);

        $data = $response->toArray(false);
        $this->assertArrayHasKey('hello', $data);
        $this->assertEquals($data['hello'], 'world');
    }

    public function testMissingMethod(): void
    {
        $response = self::createClient([], ['headers' => ['accept' => 'application/json']])->request('DELETE', '/something/that/does/not/exist/ever');
        $this->assertResponseStatusCodeSame(404);
    }
}
