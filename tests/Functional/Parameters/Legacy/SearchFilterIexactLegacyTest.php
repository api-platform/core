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

namespace ApiPlatform\Tests\Functional\Parameters\Legacy;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Legacy\IexactSearchItem;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * The difference only shows on databases with a Unicode LOWER() (PostgreSQL, MySQL), not on SQLite.
 * Remove together with the deprecated SearchFilter in 6.0.
 */
#[Group('legacy')]
final class SearchFilterIexactLegacyTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [IexactSearchItem::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema($this->getResources());

        $manager = $this->getManager();
        foreach (['École', 'Gare', 'Mairie'] as $name) {
            $item = new IexactSearchItem();
            $item->name = $name;
            $manager->persist($item);
        }

        $manager->flush();
    }

    public function testCaseInsensitiveValue(): void
    {
        $response = self::createClient()->request('GET', '/iexact_search_items?name=ÉCOLE');

        $this->assertSame(['École'], array_column($response->toArray()['member'], 'name'));
    }

    public function testCaseInsensitiveMultipleValues(): void
    {
        $response = self::createClient()->request('GET', '/iexact_search_items?name[]=ÉCOLE&name[]=GARE');

        $names = array_column($response->toArray()['member'], 'name');
        sort($names);
        $this->assertSame(['Gare', 'École'], $names);
    }
}
