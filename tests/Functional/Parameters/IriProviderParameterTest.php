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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class IriProviderParameterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [WithParameter::class, Dummy::class];
    }

    /**
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $this->recreateSchema([Dummy::class]);
    }

    public function testReadDummyIriFromQueryParameter(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $manager = $this->getManager();
        $dummy = new Dummy();
        $dummy->setName('hi');
        $manager->persist($dummy);
        $manager->flush();

        $iri = $container->get('api_platform.iri_converter')->getIriFromResource($dummy);
        $response = self::createClient()->request('GET', '/with_parameters_iris?dummy='.$iri);
        $this->assertEquals('hi', $response->toArray()['name']);
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testReadDummyIrisFromQueryParameter(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $manager = $this->getManager();
        $dummy = new Dummy();
        $dummy->setName('hi');
        $dummy2 = new Dummy();
        $dummy2->setName('ho');
        $manager->persist($dummy);
        $manager->persist($dummy2);
        $manager->flush();

        $iri2 = $container->get('api_platform.iri_converter')->getIriFromResource($dummy2);
        $iri = $container->get('api_platform.iri_converter')->getIriFromResource($dummy);
        $response = self::createClient()->request('GET', \sprintf('/with_parameters_iris?dummy[]=%s&dummy[]=%s', $iri2, $iri));
        $res = $response->toArray();
        $this->assertEquals('ho', $res['hydra:member'][0]['name']);
        $this->assertEquals('hi', $res['hydra:member'][1]['name']);
        self::assertEquals(200, $response->getStatusCode());
    }
}
