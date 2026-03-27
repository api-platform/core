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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedDateParameter as ConvertedDateParameterDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedDateParameter;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

/**
 * Tests that legacy AbstractFilter subclasses (DateFilter, etc.) work correctly
 * with QueryParameter when a nameConverter is configured.
 *
 * @see https://github.com/api-platform/core/issues/7866
 */
final class NameConverterFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [ConvertedDateParameter::class];
    }

    protected function setUp(): void
    {
        $entityClass = $this->isMongoDB() ? ConvertedDateParameterDocument::class : ConvertedDateParameter::class;

        $this->recreateSchema([$entityClass]);
        $this->loadFixtures($entityClass);
    }

    public function testDateFilterWithNameConverter(): void
    {
        $response = self::createClient()->request('GET', '/converted_date_parameters?nameConverted[after]=2025-01-15');
        $this->assertResponseIsSuccessful();
        $members = $response->toArray()['hydra:member'];
        $this->assertCount(2, $members);
    }

    public function testDateFilterBeforeWithNameConverter(): void
    {
        $response = self::createClient()->request('GET', '/converted_date_parameters?nameConverted[before]=2025-01-15');
        $this->assertResponseIsSuccessful();
        $members = $response->toArray()['hydra:member'];
        $this->assertCount(1, $members);
    }

    /**
     * @param class-string $entityClass
     */
    private function loadFixtures(string $entityClass): void
    {
        $manager = $this->getManager();

        foreach (['2025-01-01', '2025-02-01', '2025-03-01'] as $date) {
            $entity = new $entityClass();
            $entity->nameConverted = new \DateTime($date);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
