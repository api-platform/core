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

namespace ApiPlatform\Tests\Functional\Issues;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7135\Bar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7135\Foo;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Uid\Uuid;

class Issue7135Test extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Bar::class, Foo::class];
    }

    public function testValidPostRequestWithIriWhenIdentifierIsUuid(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $this->recreateSchema(self::getResources());
        $bar = $this->loadBarFixture();

        $response = self::createClient()->request('POST', '/pull-request-7135/foo/', [
            'json' => [
                'bar' => 'pull-request-7135/bar/'.$bar->id,
            ],
        ]);

        self::assertEquals(201, $response->getStatusCode());
    }

    public function testInvalidPostRequestWithIriWhenIdentifierIsUuid(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $response = self::createClient()->request('POST', '/pull-request-7135/foo/', [
            'json' => [
                'bar' => 'pull-request-7135/bar/invalid-uuid',
            ],
        ]);

        self::assertEquals(400, $response->getStatusCode());
        self::assertJsonContains(['detail' => 'Invalid IRI "pull-request-7135/bar/invalid-uuid".']);
    }

    public function testInvalidGetRequestWhenIdentifierIsUuid(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $response = self::createClient()->request('GET', '/pull-request-7135/bar/invalid-uuid');

        self::assertEquals(404, $response->getStatusCode());
    }

    protected function loadBarFixture(): Bar
    {
        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();

        $bar = new Bar(Uuid::fromString('0196b66f-66bd-780b-95fe-0ce987a32357'));
        $bar->title = 'Bar one';
        $manager->persist($bar);

        $manager->flush();

        return $bar;
    }

    protected function tearDown(): void
    {
        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        $classes = [];
        foreach (self::getResources() as $entityClass) {
            $classes[] = $manager->getClassMetadata($entityClass);
        }

        $schemaTool = new SchemaTool($manager);
        @$schemaTool->dropSchema($classes);
        parent::tearDown();
    }
}
