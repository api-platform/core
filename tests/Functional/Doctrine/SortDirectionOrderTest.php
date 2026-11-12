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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SortDirectionOrder;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\TestWith;

final class SortDirectionOrderTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SortDirectionOrder::class];
    }

    /**
     * @param list<string> $expectedNames
     */
    #[TestWith(['/sort_direction_orders', ['Alpha', 'Bravo', 'Charlie', 'Delta']])]
    #[TestWith(['/sort_direction_orders_desc', ['Delta', 'Charlie', 'Bravo', 'Alpha']])]
    public function testCollectionOrder(string $uri, array $expectedNames): void
    {
        if (!interface_exists('Doctrine\ORM\Tools\Pagination\PaginatorInterface') || !enum_exists('SortDirection')) {
            $this->markTestSkipped('SortDirection requires Doctrine ORM 3.7 or later.');
        }

        if ($this->isMongoDB()) {
            $this->markTestSkipped('This resource uses Doctrine ORM.');
        }

        $this->recreateSchema(self::getResources());
        $manager = static::getContainer()->get('doctrine')->getManager();
        foreach (['Delta', 'Alpha', 'Charlie', 'Bravo'] as $name) {
            $item = new SortDirectionOrder();
            $item->name = $name;
            $manager->persist($item);
        }
        $manager->flush();
        $manager->clear();

        $response = self::createClient()->request('GET', $uri);

        $this->assertResponseIsSuccessful();
        $this->assertSame($expectedNames, array_column($response->toArray()['hydra:member'], 'name'));
    }
}
