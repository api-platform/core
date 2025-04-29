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

namespace ApiPlatform\Tests\Functional\Doctrine;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Cart;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\CartProduct;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

final class ComputedFieldTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [CartProduct::class, Cart::class];
    }

    public function testWrongOrder(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema($this->getResources());
        $this->loadFixtures();

        $res = $this->createClient()->request('GET', '/carts?sort[totalQuantity]=wrong');
        $this->assertResponseStatusCodeSame(422);
    }

    public function testComputedField(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema($this->getResources());
        $this->loadFixtures();

        $ascReq = $this->createClient()->request('GET', '/carts?sort[totalQuantity]=asc');

        $asc = $ascReq->toArray();

        $this->assertArrayHasKey('view', $asc);
        $this->assertArrayHasKey('first', $asc['view']);
        $this->assertArrayHasKey('last', $asc['view']);
        $this->assertArrayHasKey('next', $asc['view']);

        $this->assertArrayHasKey('search', $asc);
        $this->assertEquals('/carts{?sort[totalQuantity]}', $asc['search']['template']);

        $this->assertGreaterThan(
            $asc['member'][0]['totalQuantity'],
            $asc['member'][1]['totalQuantity']
        );

        $descReq = $this->createClient()->request('GET', '/carts?sort[totalQuantity]=desc');

        $desc = $descReq->toArray();

        $this->assertLessThan(
            $desc['member'][0]['totalQuantity'],
            $desc['member'][1]['totalQuantity']
        );
    }

    protected function loadFixtures(): void
    {
        $container = static::getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();

        for ($i = 1; $i <= 10; ++$i) {
            $cart = new Cart();

            for ($j = 1; $j <= 10; ++$j) {
                $cartProduct = new CartProduct();
                $cartProduct->setQuantity((int) abs($j / $i) + 1);

                $cart->addItem($cartProduct);
            }

            $manager->persist($cart);
        }

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
        foreach ($this->getResources() as $entityClass) {
            $classes[] = $manager->getClassMetadata($entityClass);
        }

        $schemaTool = new SchemaTool($manager);
        @$schemaTool->dropSchema($classes);
        parent::tearDown();
    }
}
