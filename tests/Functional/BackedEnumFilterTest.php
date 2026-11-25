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

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7126\DummyForBackedEnumFilter;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7126\IntegerBackedEnum;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7126\StringBackedEnum;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class BackedEnumFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [DummyForBackedEnumFilter::class];
    }

    public function testFilterStringBackedEnum(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema($this->getResources());
        $this->loadFixtures();
        $route = 'backed_enum_filter';
        $response = self::createClient()->request('GET', $route.'?stringBackedEnum='.StringBackedEnum::One->value);
        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals(StringBackedEnum::One->value, $a['hydra:member'][0]['stringBackedEnum']);
    }

    public function testFilterIntegerBackedEnum(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema($this->getResources());
        $this->loadFixtures();
        $route = 'backed_enum_filter';
        $response = self::createClient()->request('GET', $route.'?integerBackedEnum='.IntegerBackedEnum::Two->value);
        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals(IntegerBackedEnum::Two->value, $a['hydra:member'][0]['integerBackedEnum']);
    }

    public function testExactFilterWithBackedEnumNativeType(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped();
        }

        $this->recreateSchema($this->getResources());
        $this->loadFixtures();
        $client = self::createClient();

        $docs = $client->request('GET', '/docs.jsonopenapi', ['headers' => ['Accept' => 'application/vnd.openapi+json']])->toArray();
        $parameters = array_values(array_filter(
            $docs['paths']['/backed_enum_native_type']['get']['parameters'],
            static fn (array $parameter): bool => 'status' === $parameter['name'],
        ));
        $this->assertSame(array_column(StringBackedEnum::cases(), 'value'), $parameters[0]['schema']['enum']);

        $members = $client->request('GET', 'backed_enum_native_type?status='.StringBackedEnum::One->value)->toArray()['hydra:member'];
        $this->assertCount(1, $members);
        $this->assertSame(StringBackedEnum::One->value, $members[0]['stringBackedEnum']);

        $client->request('GET', 'backed_enum_native_type?status=nope');
        $this->assertResponseStatusCodeSame(422);
    }

    public function loadFixtures(): void
    {
        $container = static::$kernel->getContainer();
        $registry = $container->get('doctrine');
        $manager = $registry->getManager();

        $dummyOne = new DummyForBackedEnumFilter();
        $dummyOne->setStringBackedEnum(StringBackedEnum::One);
        $dummyOne->setIntegerBackedEnum(IntegerBackedEnum::One);
        $manager->persist($dummyOne);

        $dummyTwo = new DummyForBackedEnumFilter();
        $dummyTwo->setStringBackedEnum(StringBackedEnum::Two);
        $dummyTwo->setIntegerBackedEnum(IntegerBackedEnum::Two);
        $manager->persist($dummyTwo);

        $manager->flush();
    }
}
