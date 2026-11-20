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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\GraphQl\Test\GraphQlTestTrait;
use ApiPlatform\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Issue8816\EnumFilteredActivity as DocumentEnumFilteredActivity;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8816\ActivityLevel;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8816\ActivityType;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue8816\EnumFilteredActivity;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * An "enumClass" parameter exposed as a GraphQL collection argument.
 */
final class EnumClassParameterTest extends ApiTestCase
{
    use GraphQlTestTrait;
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
        $this->recreateSchema(self::getResources());

        $class = $this->isMongoDB() ? DocumentEnumFilteredActivity::class : EnumFilteredActivity::class;
        $manager = $this->getManager();
        $manager->persist(new $class(ActivityType::Meeting, ActivityLevel::Beginner));
        $manager->persist(new $class(ActivityType::Workshop, ActivityLevel::Advanced));
        $manager->persist(new $class(ActivityType::Webinar, ActivityLevel::Expert));
        $manager->flush();
    }

    public function testArgumentsUseTheScalarTypeOfTheEnum(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              __type(name: "Query") {
                fields { name args { name type { kind name ofType { name } } } }
              }
            }
            QUERY);

        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));

        $fields = array_column($json['data']['__type']['fields'], null, 'name');
        $args = array_column($fields['enumFilteredActivities']['args'], null, 'name');

        $this->assertSame(['kind' => 'SCALAR', 'name' => 'String', 'ofType' => null], $args['type']['type']);
        $this->assertSame(['kind' => 'SCALAR', 'name' => 'Int', 'ofType' => null], $args['level']['type']);
    }

    /**
     * @param list<string> $expectedIds
     */
    #[DataProvider('provideValidArguments')]
    public function testFiltersOnEnumValues(string $arguments, array $expectedIds): void
    {
        $response = $this->executeGraphQl(\sprintf('{ enumFilteredActivities(%s) { edges { node { id } } } }', $arguments));

        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json['errors'] ?? null));
        $this->assertSame($expectedIds, array_column(array_column($json['data']['enumFilteredActivities']['edges'], 'node'), 'id'));
    }

    public static function provideValidArguments(): \Generator
    {
        yield 'string backed enum' => ['type: "workshop"', ['/enum_filtered_activities/2']];
        yield 'int backed enum' => ['level: 3', ['/enum_filtered_activities/3']];
    }
}
