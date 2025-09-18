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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse\AggregateRating;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse\GenIdFalse;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse\LevelFirst;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\GenIdFalse\LevelThird;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue6810\JsonLdContextOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7298\ImageModuleResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7298\PageResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7298\TitleModuleResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6465\Bar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6465\Foo;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

class JsonLdTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Foo::class, Bar::class, JsonLdContextOutput::class, GenIdFalse::class, AggregateRating::class, LevelFirst::class, LevelThird::class, PageResource::class, TitleModuleResource::class, ImageModuleResource::class];
    }

    /**
     * The input DTO denormalizes an existing Doctrine entity.
     */
    public function testIssue6465(): void
    {
        $container = static::getContainer();
        if ('mongodb' === $container->getParameter('kernel.environment')) {
            $this->markTestSkipped();
        }

        $response = self::createClient()->request('POST', '/foo/1/validate', [
            'json' => ['bar' => '/bar6465s/2'],
        ]);

        $res = $response->toArray();
        $this->assertEquals('Bar two', $res['title']);
    }

    public function testContextWithOutput(): void
    {
        $response = self::createClient()->request(
            'GET',
            '/json_ld_context_output',
        );
        $res = $response->toArray();
        $this->assertEquals($res['@context'], [
            '@vocab' => 'http://localhost/docs.jsonld#',
            'hydra' => 'http://www.w3.org/ns/hydra/core#',
            'foo' => 'Output/foo',
        ]);
    }

    public function testGenIdFalseOnResource(): void
    {
        $r = self::createClient()->request(
            'GET',
            '/gen_id_falsy',
        );
        $this->assertJsonContains([
            'aggregateRating' => ['ratingValue' => 2, 'ratingCount' => 3],
        ]);
        $this->assertArrayNotHasKey('@id', $r->toArray()['aggregateRating']);
    }

    public function testGenIdFalseOnNestedResource(): void
    {
        $r = self::createClient()->request(
            'GET',
            '/levelfirst/1',
        );
        $res = $r->toArray();
        $this->assertArrayNotHasKey('@id', $res['levelSecond']);
        $this->assertArrayHasKey('@id', $res['levelSecond'][0]['levelThird']);
    }

    public function testShouldIgnoreProperty(): void
    {
        $r = self::createClient()->request(
            'GET',
            '/contexts/GenIdFalse',
        );
        $this->assertArrayNotHasKey('shouldBeIgnored', $r->toArray()['@context']);
    }

    public function testIssue7298(): void
    {
        self::createClient()->request(
            'GET',
            '/page_resources/page-1',
        );
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'modules' => [
                [
                    '@type' => 'TitleModuleResource',
                    'id' => 'title-module-1',
                    'title' => 'My Title',
                ],
                [
                    '@type' => 'ImageModule',
                    'id' => 'image-module-1',
                    'url' => 'http://example.com/image.jpg',
                ],
            ],
        ]);
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        $classes = [];
        foreach ([Foo::class, Bar::class] as $entityClass) {
            $classes[] = $manager->getClassMetadata($entityClass);
        }

        try {
            $schemaTool = new SchemaTool($manager);
            @$schemaTool->createSchema($classes);
        } catch (\Exception $e) {
        }

        $foo = new Foo();
        $foo->title = 'Foo';
        $manager->persist($foo);
        $foo1 = new Foo();
        $foo1->title = 'Foo1';
        $manager->persist($foo1);
        $bar = new Bar();
        $bar->title = 'Bar one';
        $manager->persist($bar);
        $bar2 = new Bar();
        $bar2->title = 'Bar two';
        $manager->persist($bar2);
        $manager->flush();
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
        foreach ([Foo::class, Bar::class] as $entityClass) {
            $classes[] = $manager->getClassMetadata($entityClass);
        }

        $schemaTool = new SchemaTool($manager);
        @$schemaTool->dropSchema($classes);
        parent::tearDown();
    }
}
