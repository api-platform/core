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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\AgentApi;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\AgentDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Agent;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

final class QueryParameterStateOptionsTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [AgentApi::class];
    }

    public function testQueryParameterStateOptions(): void
    {
        $this->recreateSchema();
        $response = self::createClient()->request('GET', ($this->isMongoDb() ? 'agent_documents' : 'agents').'?birthday[before]=2000-01-01&birthday[after]=1990-01-01');
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();
        $agents = $data['hydra:member'];
        $this->assertCount(1, $agents);

        $validBirthdays = array_column($agents, 'birthday');
        $this->assertValidBirthdayRange($validBirthdays);
    }

    /**
     * @param array<string> $birthdays
     */
    private function assertValidBirthdayRange(array $birthdays): void
    {
        foreach ($birthdays as $birthday) {
            $this->assertLessThanOrEqual('2000-01-01T00:00:00+00:00', $birthday, "The birthday date {$birthday} exceeds the upper limit.");
            $this->assertGreaterThanOrEqual('1990-01-01T00:00:00+00:00', $birthday, "The birthday date {$birthday} is below the lower limit.");
        }
    }

    /**
     * @param array<string, mixed> $options kernel options
     */
    private function recreateSchema(array $options = []): void
    {
        self::bootKernel($options);
        $container = static::getContainer();
        $isMongoDb = $this->isMongoDb();
        $registry = $container->get($isMongoDb ? 'doctrine_mongodb' : 'doctrine');
        $resourceClass = $isMongoDb ? AgentDocument::class : Agent::class;
        $manager = $registry->getManager();

        if ($manager instanceof EntityManagerInterface) {
            $classes = $manager->getClassMetadata($resourceClass);
            $schemaTool = new SchemaTool($manager);

            @$schemaTool->dropSchema([$classes]);
            @$schemaTool->createSchema([$classes]);
        } elseif ($manager instanceof DocumentManager) {
            @$manager->getSchemaManager()->dropCollections();
        }

        $birthdays = [new \DateTimeImmutable('2002-01-01'), new \DateTimeImmutable(), new \DateTimeImmutable('1990-12-31')];
        foreach ($birthdays as $birthday) {
            $agent = (new $resourceClass())
                ->setName('Agent '.$birthday->format('Y'))
                ->setBirthday($birthday)
                ->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($agent);
        }

        $manager->flush();
    }

    private function isMongoDb(): bool
    {
        $container = static::getContainer();

        return 'mongodb' === $container->getParameter('kernel.environment');
    }
}
