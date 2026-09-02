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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\WriteUriVariableLinkResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Base64UriVariableDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class UriVariableParameterProviderTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Base64UriVariableDummy::class, WriteUriVariableLinkResource::class, Dummy::class];
    }

    protected function setUp(): void
    {
        $this->recreateSchema([Base64UriVariableDummy::class, Dummy::class]);
    }

    /**
     * @see https://github.com/api-platform/core/pull/8431
     */
    public function testLinkParameterProviderDecodesUriVariableBeforeQuery(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $manager = $this->getManager();
        $dummy = new Base64UriVariableDummy();
        $dummy->name = 'Blip';
        $manager->persist($dummy);
        $manager->flush();

        $response = self::createClient()->request('GET', '/base64_uri_variable_dummies/encoded/'.base64_encode('Blip'));
        self::assertResponseStatusCodeSame(200);
        self::assertEquals('Blip', $response->toArray()['name']);
    }

    public function testReadLinkParameterProviderWritesResolvedResourceWhenOptedIn(): void
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

        $response = self::createClient()->request('GET', '/write_uri_variable_link_resources/'.$dummy->getId());
        self::assertResponseStatusCodeSame(200);
        self::assertEquals('hi', $response->toArray()['dummyName']);
    }
}
