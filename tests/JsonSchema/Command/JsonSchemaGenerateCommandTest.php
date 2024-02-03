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

namespace ApiPlatform\Tests\JsonSchema\Command;

use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DocumentDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @author Jacques Lefebvre <jacques@les-tilleuls.coop>
 */
class JsonSchemaGenerateCommandTest extends KernelTestCase
{
    private ApplicationTester $tester;

    private string $entityClass;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $application = new Application(static::$kernel);
        $application->setCatchExceptions(true);
        $application->setAutoExit(false);

        $this->entityClass = 'mongodb' === $kernel->getEnvironment() ? DocumentDummy::class : Dummy::class;
        $this->tester = new ApplicationTester($application);
    }

    public function testExecuteWithoutOption(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass]);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithItemOperationGet(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies/{id}{._format}_get', '--type' => 'output']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithCollectionOperationGet(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies{._format}_get_collection', '--type' => 'output']);

        $this->assertJson($this->tester->getDisplay());
    }

    public function testExecuteWithJsonldFormatOption(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies{._format}_post', '--format' => 'jsonld', '--type' => 'output']);
        $result = $this->tester->getDisplay();

        $this->assertStringContainsString('@id', $result);
        $this->assertStringContainsString('@context', $result);
        $this->assertStringContainsString('@type', $result);
    }

    public function testExecuteWithJsonldTypeInput(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => $this->entityClass, '--operation' => '_api_/dummies{._format}_post', '--format' => 'jsonld', '--type' => 'input']);
        $result = $this->tester->getDisplay();

        $this->assertStringNotContainsString('@id', $result);
        $this->assertStringNotContainsString('@context', $result);
        $this->assertStringNotContainsString('@type', $result);
    }

    /**
     * Test issue #5501, the locations relation inside BrokenDocs is a Resource (named Related) but its only operation is a NotExposed.
     * Still, serializer groups are set, and therefore it is a "readableLink" so we actually want to compute the schema, even if it's not accessible
     * directly, it is accessible through that relation.
     */
    public function testExecuteWithNotExposedResourceAndReadableLink(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5501\BrokenDocs', '--type' => 'output']);
        $result = $this->tester->getDisplay();

        $this->assertStringContainsString('Related.jsonld-location.read_collection', $result);
    }

    /**
     * When serializer groups are present the Schema should have an embed resource. #5470 breaks array references when serializer groups are present.
     */
    public function testArraySchemaWithReference(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5793\BagOfTests', '--type' => 'input']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['BagOfTests.jsonld-write']['properties']['tests'], [
            'type' => 'string',
            'foo' => 'bar',
        ]);

        $this->assertEquals($json['definitions']['BagOfTests.jsonld-write']['properties']['nonResourceTests'], [
            'type' => 'array',
            'items' => [
                '$ref' => '#/definitions/NonResourceTestEntity.jsonld-write',
            ],
        ]);

        $this->assertEquals($json['definitions']['BagOfTests.jsonld-write']['properties']['description'], [
            'maxLength' => 255,
        ]);

        $this->assertEquals($json['definitions']['BagOfTests.jsonld-write']['properties']['type'], [
            '$ref' => '#/definitions/TestEntity.jsonld-write',
        ]);
    }

    /**
     * TODO: add deprecation (TypeFactory will be deprecated in api platform 3.3).
     *
     * @group legacy
     */
    public function testArraySchemaWithTypeFactory(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5896\Foo', '--type' => 'output']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['Foo.jsonld']['properties']['expiration'], ['type' => 'string', 'format' => 'date']);
    }

    /**
     * Test issue #5998.
     */
    public function testWritableNonResourceRef(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5998\SaveProduct', '--type' => 'input']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertEquals($json['definitions']['SaveProduct.jsonld']['properties']['codes']['items']['$ref'], '#/definitions/ProductCode.jsonld');
    }

    /**
     * Test related Schema keeps json-ld context.
     */
    public function testSubSchemaJsonLd(): void
    {
        $this->tester->run(['command' => 'api:json-schema:generate', 'resource' => 'ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy', '--type' => 'output', '--format' => 'jsonld']);
        $result = $this->tester->getDisplay();
        $json = json_decode($result, associative: true);

        $this->assertArrayHasKey('@id', $json['definitions']['ThirdLevel.jsonld-friends']['properties']);
    }
}
