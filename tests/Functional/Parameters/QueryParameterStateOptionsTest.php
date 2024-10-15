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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\AgentDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Agent;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class QueryParameterStateOptionsTest extends ApiTestCase
{
    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testWithBirthdayDateFilter(): void
    {
        $this->recreateSchema();
        $response = self::createClient()->request('GET', 'agent_simples?birthday[before]=2000-01-01&birthday[after]=1990-01-01');
        $this->assertResponseIsSuccessful();

        $agents = $this->getResponseData($response);
        $this->assertCount(1, $agents);

        $validBirthdays = array_column($agents, 'birthday');
        $this->assertValidBirthdayRange($validBirthdays);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testQueryParameterStateOptions(): void
    {
        $this->recreateSchema();
        $response = self::createClient()->request('GET', 'agents?bla[birthday][before]=2000-01-01&bla[birthday][after]=1990-01-01');
        $this->assertResponseIsSuccessful();

        $agents = $this->getResponseData($response);
        $this->assertCount(1, $agents);

        $validBirthdays = array_column($agents, 'birthday');
        $this->assertValidBirthdayRange($validBirthdays);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function getResponseData(ResponseInterface $response): array
    {
        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data, '"hydra:member" key does not contain an array');

        return $data['hydra:member'];
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

        $isMongoDb = 'mongodb' === $container->getParameter('kernel.environment');
        $registry = $this->getContainer()->get($isMongoDb ? 'doctrine_mongodb' : 'doctrine');
        $resourceClass = $isMongoDb ? AgentDocument::class : Agent::class;

        $manager = $registry->getManager();
        if ($manager instanceof EntityManagerInterface) {
            $classes = $manager->getClassMetadata($resourceClass);
            $schemaTool = new SchemaTool($manager);

            @$schemaTool->dropSchema([$classes]);
            @$schemaTool->createSchema([$classes]);
        }
        if ($manager instanceof DocumentManager) {
            @$manager->getSchemaManager()->dropCollections();
        }

        $birthdays = [new \DateTimeImmutable('2002-01-01'), new \DateTimeImmutable(), new \DateTimeImmutable('1990-12-31')];
        foreach ($birthdays as $birthday) {
            $agent = (new $resourceClass())
                ->setName('Agent '.$birthday->format('Y'))
                ->setApiKey('api_key_'.$birthday->format('Y'))
                ->setBirthday($birthday)
                ->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($agent);
        }

        try {
            $manager->flush();
        } catch (MongoDBException|\Throwable $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
