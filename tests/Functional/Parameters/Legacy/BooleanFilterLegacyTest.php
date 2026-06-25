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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Legacy\FilteredBooleanParameter as FilteredBooleanParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Legacy\FilteredBooleanParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Regression coverage for the deprecated BooleanFilter. The canonical equivalent
 * (ExactFilter + boolean nativeType) is covered by
 * ApiPlatform\Tests\Functional\Parameters\BooleanFilterTest.
 * Remove together with the deprecated filter in 6.0.
 */
#[Group('legacy')]
final class BooleanFilterLegacyTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [FilteredBooleanParameter::class];
    }

    /**
     * @throws MongoDBException
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        $entityClass = $this->isMongoDB() ? FilteredBooleanParameterDocument::class : FilteredBooleanParameter::class;

        $this->recreateSchema([$entityClass]);
        $this->loadFixtures($entityClass);
    }

    #[DataProvider('booleanFilterScenariosProvider')]
    public function testBooleanFilterResponses(string $url, int $expectedActiveItemCount, bool $expectedActiveStatus): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $this->assertCount($expectedActiveItemCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedActiveItemCount, $url));

        foreach ($filteredItems as $item) {
            $this->assertSame($expectedActiveStatus, $item['active'], \sprintf("Expected 'active' to be %s", $expectedActiveStatus));
        }
    }

    public static function booleanFilterScenariosProvider(): \Generator
    {
        yield 'active_true' => ['/legacy_filtered_boolean_parameters?active=true', 2, true];
        yield 'active_false' => ['/legacy_filtered_boolean_parameters?active=false', 1, false];
        yield 'active_numeric_1' => ['/legacy_filtered_boolean_parameters?active=1', 2, true];
        yield 'active_numeric_0' => ['/legacy_filtered_boolean_parameters?active=0', 1, false];
        yield 'enabled_alias_true' => ['/legacy_filtered_boolean_parameters?enabled=true', 2, true];
        yield 'enabled_alias_false' => ['/legacy_filtered_boolean_parameters?enabled=false', 1, false];
        yield 'enabled_alias_numeric_1' => ['/legacy_filtered_boolean_parameters?enabled=1', 2, true];
        yield 'enabled_alias_numeric_0' => ['/legacy_filtered_boolean_parameters?enabled=0', 1, false];
    }

    #[DataProvider('booleanFilterNullAndEmptyScenariosProvider')]
    public function testBooleanFilterWithNullAndEmptyValues(string $url): void
    {
        $response = self::createClient()->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $responseData = $response->toArray();
        $filteredItems = $responseData['hydra:member'];

        $expectedItemCount = 3;
        $this->assertCount($expectedItemCount, $filteredItems, \sprintf('Expected %d items for URL %s', $expectedItemCount, $url));
    }

    public static function booleanFilterNullAndEmptyScenariosProvider(): \Generator
    {
        yield 'active_null_value' => ['/legacy_filtered_boolean_parameters?active=null'];
        yield 'active_empty_value' => ['/legacy_filtered_boolean_parameters?active='];
        yield 'enabled_alias_null_value' => ['/legacy_filtered_boolean_parameters?enabled=null'];
        yield 'enabled_alias_empty_value' => ['/legacy_filtered_boolean_parameters?enabled='];
    }

    /**
     * @throws \Throwable
     * @throws MongoDBException
     */
    private function loadFixtures(string $entityClass): void
    {
        $manager = $this->getManager();

        $booleanStates = [true, true, false, null];
        foreach ($booleanStates as $activeValue) {
            $entity = new $entityClass(active: $activeValue);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
