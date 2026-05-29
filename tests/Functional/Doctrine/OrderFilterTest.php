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

namespace ApiPlatform\Tests\Functional\Doctrine;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedInteger as ConvertedIntegerDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\EmbeddableDummy as EmbeddableDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\EmbeddedDummy as EmbeddedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedInteger;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddableDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\TestWith;

final class OrderFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [
            Dummy::class,
            RelatedDummy::class,
            EmbeddedDummy::class,
            ConvertedInteger::class,
        ];
    }

    #[TestWith(['order[id]=asc', ['/dummies/1', '/dummies/2', '/dummies/3']])]
    #[TestWith(['order[id]=desc', ['/dummies/30', '/dummies/29', '/dummies/28']])]
    #[TestWith(['order[name]=asc', ['/dummies/1', '/dummies/10', '/dummies/11']])]
    #[TestWith(['order[name]=desc', ['/dummies/9', '/dummies/8', '/dummies/7']])]
    public function testOrderDummies(string $query, array $expectedIds): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?'.$query, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame($expectedIds, array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']));
    }

    public function testOrderByMultipleProperties(): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?order[name]=desc&order[id]=desc', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(
            ['/dummies/39', '/dummies/9', '/dummies/38'],
            array_map(static fn (array $i): string => $i['@id'], $data['hydra:member'])
        );
    }

    public function testOrderByAssociation(): void
    {
        $resource = $this->dummyClass();
        $relatedResource = $this->relatedDummyClass();
        $this->recreateSchema([$resource, $relatedResource]);
        $this->createDummiesWithRelatedDummy($resource, $relatedResource, 30);

        $response = self::createClient()->request('GET', '/dummies?order[relatedDummy]=asc', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(
            ['/dummies/1', '/dummies/2', '/dummies/3'],
            array_map(static fn (array $i): string => $i['@id'], $data['hydra:member'])
        );
    }

    public function testOrderByEmbedded(): void
    {
        $embeddedClass = $this->isMongoDB() ? EmbeddedDummyDocument::class : EmbeddedDummy::class;
        $embeddableClass = $this->isMongoDB() ? EmbeddableDummyDocument::class : EmbeddableDummy::class;
        $this->recreateSchema([$embeddedClass]);

        $manager = $this->getManager();
        for ($i = 1; $i <= 30; ++$i) {
            $embeddable = new $embeddableClass();
            $embeddable->setDummyName('EmbeddedDummy #'.$i);
            $dummy = new $embeddedClass();
            $dummy->setName('Dummy #'.$i);
            $dummy->setEmbeddedDummy($embeddable);
            $manager->persist($dummy);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/embedded_dummies?order[embeddedDummy]=asc', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(
            ['/embedded_dummies/1', '/embedded_dummies/2', '/embedded_dummies/3'],
            array_map(static fn (array $i): string => $i['@id'], $data['hydra:member'])
        );
    }

    public function testOrderByEmbeddedStringWithoutValueReturns422(): void
    {
        $resource = $this->isMongoDB() ? EmbeddedDummyDocument::class : EmbeddedDummy::class;
        $this->recreateSchema([$resource]);

        self::createClient()->request('GET', '/embedded_dummies?order[embeddedDummy.dummyName]', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    #[TestWith(['order[alias]=asc'])]
    #[TestWith(['order[alias]=desc'])]
    #[TestWith(['order[unknown]=asc'])]
    #[TestWith(['order[unknown]=desc'])]
    public function testOrderByUnsupportedProperty(string $query): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?'.$query, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(
            ['/dummies/1', '/dummies/2', '/dummies/3'],
            array_map(static fn (array $i): string => $i['@id'], $data['hydra:member'])
        );
    }

    public function testOrderByRelatedProperty(): void
    {
        $resource = $this->dummyClass();
        $relatedResource = $this->relatedDummyClass();
        $this->recreateSchema([$resource, $relatedResource]);
        $this->createDummiesWithRelatedDummy($resource, $relatedResource, 2);

        $response = self::createClient()->request('GET', '/dummies?order[relatedDummy.name]=desc', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(
            ['/dummies/2', '/dummies/1'],
            array_map(static fn (array $i): string => $i['@id'], $data['hydra:member'])
        );
    }

    public function testOrderUsingNameConverter(): void
    {
        $resource = $this->isMongoDB() ? ConvertedIntegerDocument::class : ConvertedInteger::class;
        $this->recreateSchema([$resource]);
        $manager = $this->getManager();
        for ($i = 1; $i <= 3; ++$i) {
            $entity = new $resource();
            $entity->nameConverted = $i;
            $manager->persist($entity);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/converted_integers?order[name_converted]=desc', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(
            ['/converted_integers/3', '/converted_integers/2', '/converted_integers/1'],
            array_map(static fn (array $i): string => $i['@id'], $data['hydra:member'])
        );
        foreach ($data['hydra:member'] as $member) {
            $this->assertSame('ConvertedInteger', $member['@type']);
            $this->assertIsInt($member['name_converted']);
        }

        $this->assertSame('hydra:IriTemplate', $data['hydra:search']['@type']);
        $this->assertSame('BasicRepresentation', $data['hydra:search']['hydra:variableRepresentation']);
        $this->assertStringMatchesFormat('/converted_integers{?%a}', $data['hydra:search']['hydra:template']);
        $variables = array_map(static fn (array $m): string => $m['variable'], $data['hydra:search']['hydra:mapping']);
        sort($variables);
        $this->assertSame([
            'name_converted',
            'name_converted[]',
            'name_converted[between]',
            'name_converted[gt]',
            'name_converted[gte]',
            'name_converted[lt]',
            'name_converted[lte]',
            'order[name_converted]',
        ], $variables);
        foreach ($data['hydra:search']['hydra:mapping'] as $mapping) {
            $this->assertSame('IriTemplateMapping', $mapping['@type']);
            $this->assertSame('name_converted', $mapping['property']);
        }
    }

    public function testOrderListSyntaxIsAccepted(): void
    {
        $resource = $this->isMongoDB() ? ConvertedIntegerDocument::class : ConvertedInteger::class;
        $this->recreateSchema([$resource]);
        $manager = $this->getManager();
        for ($i = 1; $i <= 3; ++$i) {
            $entity = new $resource();
            $entity->nameConverted = $i;
            $manager->persist($entity);
        }
        $manager->flush();

        self::createClient()->request('GET', '/converted_integers?order[]=desc', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
    }

    /**
     * @return class-string
     */
    private function dummyClass(): string
    {
        return $this->isMongoDB() ? DummyDocument::class : Dummy::class;
    }

    /**
     * @return class-string
     */
    private function relatedDummyClass(): string
    {
        return $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class;
    }

    /**
     * @param class-string $resource
     */
    private function createDummies(string $resource, int $nb): void
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new $resource();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDummy('SomeDummyTest'.$i);
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->nameConverted = 'Converted '.$i;
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    /**
     * @param class-string $resource
     * @param class-string $relatedResource
     */
    private function createDummiesWithRelatedDummy(string $resource, string $relatedResource, int $nb): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $relatedDummy = new $relatedResource();
            $relatedDummy->setName('RelatedDummy #'.$i);

            $dummy = new $resource();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->nameConverted = "Converted $i";
            $dummy->setRelatedDummy($relatedDummy);

            $manager->persist($relatedDummy);
            $manager->persist($dummy);
        }
        $manager->flush();
    }
}
