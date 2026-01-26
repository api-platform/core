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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\SoMany;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CursorPaginationNoNextTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [SoMany::class];
    }

    /**
     * Test that hydra:next is not present when pagination would return no items.
     */
    public function testCursorPaginationNoNextWhenNoMoreItems(): void
    {
        $this->recreateSchema([SoMany::class]);
        $this->loadFixtures();

        $client = self::createClient();

        // Test first page - should have hydra:next
        $response = $client->request('GET', '/so_manies?itemsPerPage=3');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayHasKey('hydra:view', $data);
        $this->assertArrayHasKey('hydra:next', $data['hydra:view'], 'First page should have hydra:next');

        // Get the items from first page
        $items = $data['hydra:member'];
        $this->assertCount(3, $items);

        // Request page 2 (should have 2 items, so hydra:next should be absent)
        $nextUrl = parse_url($data['hydra:view']['hydra:next'], \PHP_URL_PATH).'?'.parse_url($data['hydra:view']['hydra:next'], \PHP_URL_QUERY);
        $response = $client->request('GET', $nextUrl);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayHasKey('hydra:view', $data);
        $items = $data['hydra:member'];
        $this->assertCount(2, $items, 'Second page should have 2 items');
        $this->assertArrayNotHasKey('hydra:next', $data['hydra:view'], 'Last page should not have hydra:next when no more items would be returned');
        $this->assertArrayHasKey('hydra:previous', $data['hydra:view'], 'Last page should have hydra:previous');
    }

    private function loadFixtures(): void
    {
        $manager = $this->getManager();

        // Create exactly 5 items so with itemsPerPage=3, page 2 will have 2 items
        for ($i = 1; $i <= 5; ++$i) {
            $soMany = new SoMany();
            $soMany->content = 'Item '.$i;
            $manager->persist($soMany);
        }

        $manager->flush();
    }
}
