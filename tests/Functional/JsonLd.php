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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PostWithCollectionIri;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\PostWithCollectionIriItem;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6465\Bar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6465\Foo;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

class JsonLd extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Foo::class, Bar::class, PostWithCollectionIri::class, PostWithCollectionIriItem::class];
    }

    /**
     * The input DTO denormalizes an existing Doctrine entity.
     */
    public function testIssue6465(): void
    {
        $this->recreateSchema([Foo::class, Bar::class]);
        $registry = static::getContainer()->get('doctrine')->getManager();
        $foo->title = 'Foo';
        $manager->persist($foo);
        $bar = new Bar();
        $bar->title = 'Bar one';
        $manager->persist($bar);
        $bar2 = new Bar();
        $bar2->title = 'Bar two';
        $manager->persist($bar2);
        $manager->flush();

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

    public function testPostWithCollectionIri(): void
    {
        $response = self::createClient()->request('POST', '/post_with_iri_collection/1/slug', [
            'json' => [],
        ]);

        $res = $response->toArray(false);
        dd($res);
        $this->assertEquals($res['@id'], '/post_with_iri_collection/1/slug');
        $this->assertEquals($res['hydra:member'][0]['id'], '/post_with_iri_collection_item/1');
        $this->assertEquals($res['hydra:member'][1]['id'], '/post_with_iri_collection_item/2');
    }
}
