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

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Person;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GenderTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpClient\HttpOptions;

final class BackedEnumPropertyTest extends ApiTestCase
{
    public function testJson(): void
    {
        $person = $this->createPerson();

        self::createClient()->request('GET', '/people/'.$person->getId(), ['headers' => ['Accept' => 'application/json']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            'genderType' => GenderTypeEnum::FEMALE->value,
            'name' => 'Sonja',
            'academicGrades' => [],
            'pets' => [],
        ]);
    }

    /** @group legacy */
    public function testGraphQl(): void
    {
        $person = $this->createPerson();

        $query = <<<'GRAPHQL'
query GetPerson($identifier: ID!) {
    person(id: $identifier) {
        genderType
    }
}
GRAPHQL;
        $options = (new HttpOptions())
            ->setJson(['query' => $query, 'variables' => ['identifier' => '/people/'.$person->getId()]])
            ->setHeaders(['Content-Type' => 'application/json']);
        self::createClient()->request('POST', '/graphql', $options->toArray());

        $this->assertResponseIsSuccessful();
        $this->assertJsonEquals([
            'data' => [
                'person' => [
                    'genderType' => GenderTypeEnum::FEMALE->name,
                ],
            ],
        ]);
    }

    private function createPerson(): Person
    {
        $this->recreateSchema();

        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        $person = new Person();
        $person->name = 'Sonja';
        $person->genderType = GenderTypeEnum::FEMALE;
        $manager->persist($person);
        $manager->flush();

        return $person;
    }

    private function recreateSchema(array $options = []): void
    {
        self::bootKernel($options);

        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        /** @var ClassMetadata[] $classes */
        $classes = $manager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($manager);

        @$schemaTool->dropSchema($classes);
        @$schemaTool->createSchema($classes);
    }
}
