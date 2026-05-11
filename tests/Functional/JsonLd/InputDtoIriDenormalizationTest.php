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

namespace ApiPlatform\Tests\Functional\JsonLd;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6465\Bar;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue6465\Foo;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

class InputDtoIriDenormalizationTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Foo::class, Bar::class];
    }

    protected function setUp(): void
    {
        self::bootKernel();

        if ($this->isMongoDB()) {
            $this->markTestSkipped('This test uses Doctrine ORM entities without MongoDB equivalents.');
        }

        $this->recreateSchema([Foo::class, Bar::class]);

        $manager = $this->getManager();
        $foo = new Foo();
        $foo->title = 'Foo';
        $manager->persist($foo);
        $bar = new Bar();
        $bar->title = 'Bar one';
        $manager->persist($bar);
        $bar2 = new Bar();
        $bar2->title = 'Bar two';
        $manager->persist($bar2);
        $manager->flush();
    }

    public function testInputDtoDenormalizesEntityFromIri(): void
    {
        $response = self::createClient()->request('POST', '/foo/1/validate', [
            'json' => ['bar' => '/bar6465s/2'],
        ]);

        $res = $response->toArray();
        $this->assertEquals('Bar two', $res['title']);
    }
}
