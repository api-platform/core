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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ParameterOnProperties as DocumentParameterOnProperties;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ParameterOnPropertiesWithHeaderParameter as DocumentParameterOnPropertiesWithHeaderParameter;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ParameterOnProperties;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ParameterOnPropertiesWithHeaderParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;

final class ParameterOnPropertiesTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ParameterOnProperties::class, ParameterOnPropertiesWithHeaderParameter::class];
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $entities = $this->isMongoDB()
            ? [DocumentParameterOnProperties::class, DocumentParameterOnPropertiesWithHeaderParameter::class]
            : [ParameterOnProperties::class, ParameterOnPropertiesWithHeaderParameter::class];

        $this->recreateSchema($entities);
        $this->loadFixtures();
    }

    public function testQueryParameterOnProperty(): void
    {
        $response = self::createClient()->request('GET', 'parameter_on_properties?qname=oo');
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();

        $this->assertArrayHasKey('hydra:member', $responseData);
        $members = $responseData['hydra:member'];

        $this->assertCount(2, $members);
        $this->assertSame('foo', $members[0]['name']);
        $this->assertSame('qoox', $members[1]['name']);
    }

    public function testHeaderParameterOnProperty(): void
    {
        $response = self::createClient()->request('GET', 'parameter_on_properties_with_header_parameter', [
            'headers' => [
                'X-Authorization' => 'Bearer token123',
            ],
        ]);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();

        $this->assertArrayHasKey('hydra:member', $responseData);
        $members = $responseData['hydra:member'];

        $this->assertCount(1, $members);
        $this->assertSame('test-auth', $members[0]['authToken']);
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        $parameterOnPropertiesClass = $this->isMongoDB() ? DocumentParameterOnProperties::class : ParameterOnProperties::class;

        $manager->persist(new $parameterOnPropertiesClass('foo', 'bar'));
        $manager->persist(new $parameterOnPropertiesClass('baz', 'qux'));
        $manager->persist(new $parameterOnPropertiesClass('qoox', 'corge'));

        $headerParameterClass = $this->isMongoDB() ? DocumentParameterOnPropertiesWithHeaderParameter::class : ParameterOnPropertiesWithHeaderParameter::class;

        $manager->persist(new $headerParameterClass('test-auth'));

        $manager->flush();
    }
}
