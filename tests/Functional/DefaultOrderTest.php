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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Foo;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\FooDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SoMany;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class DefaultOrderTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Foo::class, FooDummy::class, Dummy::class, SoMany::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema($this->getResources());
    }

    private function seedFoos(): void
    {
        $manager = $this->getManager();
        $names = ['Hawsepipe', 'Sthenelus', 'Ephesian', 'Separativeness', 'Balbo'];
        $bars = ['Lorem', 'Ipsum', 'Dolor', 'Sit', 'Amet'];
        for ($i = 0; $i < 5; ++$i) {
            $foo = new Foo();
            $foo->setName($names[$i]);
            $foo->setBar($bars[$i]);
            $manager->persist($foo);
        }
        $manager->flush();
        $manager->clear();
    }

    public function testDefaultOrderOnFooCollection(): void
    {
        $this->seedFoos();

        self::createClient()->request('GET', '/foos?itemsPerPage=10');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $this->assertJsonEquals([
            '@context' => '/contexts/Foo',
            '@id' => '/foos',
            '@type' => 'hydra:Collection',
            'hydra:member' => [
                ['@id' => '/foos/5', '@type' => 'Foo', 'id' => 5, 'name' => 'Balbo', 'bar' => 'Amet'],
                ['@id' => '/foos/3', '@type' => 'Foo', 'id' => 3, 'name' => 'Ephesian', 'bar' => 'Dolor'],
                ['@id' => '/foos/2', '@type' => 'Foo', 'id' => 2, 'name' => 'Sthenelus', 'bar' => 'Ipsum'],
                ['@id' => '/foos/1', '@type' => 'Foo', 'id' => 1, 'name' => 'Hawsepipe', 'bar' => 'Lorem'],
                ['@id' => '/foos/4', '@type' => 'Foo', 'id' => 4, 'name' => 'Separativeness', 'bar' => 'Sit'],
            ],
            'hydra:totalItems' => 5,
            'hydra:view' => [
                '@id' => '/foos?itemsPerPage=10',
                '@type' => 'hydra:PartialCollectionView',
            ],
        ]);
    }

    public function testDefaultOrderByAssociationOnFooDummy(): void
    {
        $manager = $this->getManager();
        $names = ['Hawsepipe', 'Ephesian', 'Sthenelus', 'Separativeness', 'Balbo'];
        $dummies = ['Lorem', 'Ipsum', 'Dolor', 'Sit', 'Amet'];
        for ($i = 0; $i < 5; ++$i) {
            $dummy = new Dummy();
            $dummy->setName($dummies[$i]);
            $foo = new FooDummy();
            $foo->setName($names[$i]);
            $foo->setDummy($dummy);
            for ($j = 0; $j < 3; ++$j) {
                $soMany = new SoMany();
                $soMany->content = "So many $j";
                $soMany->fooDummy = $foo;
                $foo->soManies->add($soMany);
            }
            $manager->persist($foo);
        }
        $manager->flush();
        $manager->clear();

        $response = self::createClient()->request('GET', '/foo_dummies?itemsPerPage=10');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $data = $response->toArray();
        $names = array_column($data['hydra:member'], 'name');
        $this->assertSame(['Balbo', 'Sthenelus', 'Ephesian', 'Hawsepipe', 'Separativeness'], $names);
        $this->assertSame(5, $data['hydra:totalItems']);
    }

    public function testCustomCollectionOrderAsc(): void
    {
        $this->seedFoos();

        $response = self::createClient()->request('GET', '/custom_collection_asc_foos?itemsPerPage=10');

        $this->assertResponseStatusCodeSame(200);
        $names = array_column($response->toArray()['hydra:member'], 'name');
        $this->assertSame(['Balbo', 'Ephesian', 'Hawsepipe', 'Separativeness', 'Sthenelus'], $names);
    }

    public function testCustomCollectionOrderDesc(): void
    {
        $this->seedFoos();

        $response = self::createClient()->request('GET', '/custom_collection_desc_foos?itemsPerPage=10');

        $this->assertResponseStatusCodeSame(200);
        $names = array_column($response->toArray()['hydra:member'], 'name');
        $this->assertSame(['Sthenelus', 'Separativeness', 'Hawsepipe', 'Ephesian', 'Balbo'], $names);
    }
}
