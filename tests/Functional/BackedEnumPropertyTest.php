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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Person as PersonDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Person;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\PersonToPet;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Pet;
use ApiPlatform\Tests\Fixtures\TestBundle\Enum\GenderTypeEnum;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use Symfony\Component\HttpClient\HttpOptions;

final class BackedEnumPropertyTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Person::class, Pet::class];
    }

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

    #[\PHPUnit\Framework\Attributes\Group('legacy')]
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

    private function createPerson(): Person|PersonDocument
    {
        $this->recreateSchema([Person::class, PersonToPet::class, Pet::class]);

        $manager = $this->getManager();
        $person = $this->isMongoDB() ? new PersonDocument() : new Person();
        $person->name = 'Sonja';
        $person->genderType = GenderTypeEnum::FEMALE;
        $manager->persist($person);
        $manager->flush();

        return $person;
    }
}
