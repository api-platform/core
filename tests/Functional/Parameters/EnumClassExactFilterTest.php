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

namespace ApiPlatform\Tests\Functional\Parameters;

use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8816\ActivityLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8816\ActivityType;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8816\EnumFilteredActivity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * An "enumClass" parameter combined with a filter accepting both a value and a list of values.
 *
 * @see https://github.com/api-platform/core/issues/8816
 */
final class EnumClassExactFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [EnumFilteredActivity::class];
    }

    protected function setUp(): void
    {
        if ($this->isMongoDB()) {
            $this->markTestSkipped('The fixture uses the Doctrine ORM ExactFilter.');
        }

        $this->recreateSchema(self::getResources());

        $manager = static::getContainer()->get('doctrine')->getManager();
        $manager->persist(new EnumFilteredActivity(ActivityType::Meeting, ActivityLevel::Beginner));
        $manager->persist(new EnumFilteredActivity(ActivityType::Workshop, ActivityLevel::Advanced));
        $manager->persist(new EnumFilteredActivity(ActivityType::Webinar, ActivityLevel::Expert));
        $manager->flush();
    }

    /**
     * @param list<string> $expectedTypes
     */
    #[DataProvider('provideValidQueries')]
    public function testFiltersOnEnumValues(string $query, array $expectedTypes): void
    {
        $response = self::createClient()->request('GET', '/enum_filtered_activities?'.$query);

        $this->assertResponseIsSuccessful();
        $this->assertSame($expectedTypes, array_column($response->toArray()['hydra:member'], 'type'));
    }

    public static function provideValidQueries(): \Generator
    {
        yield 'string backed enum, single value' => ['type=workshop', ['workshop']];
        yield 'string backed enum, list of values' => ['type[]=meeting&type[]=webinar', ['meeting', 'webinar']];
        yield 'int backed enum, single value' => ['level=2', ['workshop']];
        yield 'int backed enum, list of values' => ['level[]=1&level[]=3', ['meeting', 'webinar']];
    }

    #[DataProvider('provideInvalidQueries')]
    public function testRejectsValuesOutsideTheEnum(string $query): void
    {
        self::createClient()->request('GET', '/enum_filtered_activities?'.$query);

        $this->assertResponseStatusCodeSame(422);
    }

    public static function provideInvalidQueries(): \Generator
    {
        yield 'string backed enum, single value' => ['type=party'];
        yield 'string backed enum, list with an unknown value' => ['type[]=meeting&type[]=party'];
        yield 'int backed enum, single value' => ['level=4'];
        yield 'int backed enum, list with an unknown value' => ['level[]=1&level[]=4'];
        yield 'int backed enum, non numeric value' => ['level=foo'];
    }

    public function testOpenApiDocumentsTheEnumOnBothForms(): void
    {
        $response = self::createClient()->request('GET', '/docs', [
            'headers' => ['Accept' => 'application/vnd.openapi+json'],
        ]);
        $this->assertResponseIsSuccessful();

        $parameters = array_column($response->toArray()['paths']['/enum_filtered_activities']['get']['parameters'], null, 'name');

        $this->assertSame(['type' => 'string', 'enum' => ['meeting', 'workshop', 'webinar']], $parameters['type']['schema']);
        $this->assertSame(['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['meeting', 'workshop', 'webinar']]], $parameters['type[]']['schema']);
        $this->assertSame(['type' => 'integer', 'enum' => [1, 2, 3]], $parameters['level']['schema']);
        $this->assertSame(['type' => 'array', 'items' => ['type' => 'integer', 'enum' => [1, 2, 3]]], $parameters['level[]']['schema']);
    }
}
