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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7126\IntegerBackedEnum;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue7126\StringBackedEnum;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Legacy\DummyForBackedEnumFilter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Regression coverage for the deprecated #[ApiFilter(BackedEnumFilter)] attribute path.
 * The canonical equivalent is covered by ApiPlatform\Tests\Functional\BackedEnumFilterTest.
 * Remove together with the deprecated filters in 6.0.
 */
#[Group('legacy')]
final class BackedEnumFilterLegacyTest extends ApiTestCase
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
        $response = self::createClient()->request('GET', 'legacy_backed_enum_filter?stringBackedEnum='.StringBackedEnum::One->value);
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
        $response = self::createClient()->request('GET', 'legacy_backed_enum_filter?integerBackedEnum='.IntegerBackedEnum::Two->value);
        $a = $response->toArray();
        $this->assertCount(1, $a['hydra:member']);
        $this->assertEquals(IntegerBackedEnum::Two->value, $a['hydra:member'][0]['integerBackedEnum']);
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
