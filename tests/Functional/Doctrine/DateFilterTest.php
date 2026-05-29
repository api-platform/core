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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\ConvertedDate as ConvertedDateDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDate as DummyDateDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyImmutableDate as DummyImmutableDateDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\EmbeddableDummy as EmbeddableDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\EmbeddedDummy as EmbeddedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedDate;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDate;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyImmutableDate;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddableDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\EmbeddedDummy;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\TestWith;

final class DateFilterTest extends ApiTestCase
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
            DummyDate::class,
            DummyImmutableDate::class,
            ConvertedDate::class,
        ];
    }

    #[TestWith(['dummyDate[after]=2015-04-28', 2])]
    #[TestWith(['dummyDate[before]=2015-04-05', 5])]
    #[TestWith(['dummyDate[after]=2015-04-28T00:00:00%2B00:00', 2])]
    #[TestWith(['dummyDate[before]=2015-04-05Z', 5])]
    #[TestWith(['dummyDate[before]=2015-04-05&dummyDate[after]=2015-04-05', 1])]
    #[TestWith(['dummyDate[after]=2015-04-05&dummyDate[before]=2015-04-05', 1])]
    #[TestWith(['dummyDate[after]=2015-04-06&dummyDate[before]=2015-04-04', 0])]
    public function testDummyDateFilter(string $query, int $expectedTotal): void
    {
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummiesWithDate($resource, 30);

        $response = self::createClient()->request('GET', '/dummies?'.$query, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame($expectedTotal, $response->toArray()['hydra:totalItems']);
    }

    #[TestWith(['relatedDummy.dummyDate[after]=2015-04-28', 3])]
    #[TestWith(['relatedDummy.dummyDate[after]=2015-04-28&relatedDummy_dummyDate[after]=2015-04-28', 3])]
    #[TestWith(['relatedDummy.dummyDate[after]=2015-04-28T00:00:00%2B00:00', 3])]
    public function testAssociationDateFilter(string $query, int $expectedTotal): void
    {
        $resource = $this->dummyClass();
        $relatedResource = $this->relatedDummyClass();
        $this->recreateSchema([$resource, $relatedResource]);
        $this->createDummiesWithDateAndRelatedDummy($resource, $relatedResource, 30);

        $response = self::createClient()->request('GET', '/dummies?'.$query, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame($expectedTotal, $response->toArray()['hydra:totalItems']);
    }

    public function testAssociationDateFilterWithEmptyResultSet(): void
    {
        $resource = $this->dummyClass();
        $relatedResource = $this->relatedDummyClass();
        $this->recreateSchema([$resource, $relatedResource]);
        $this->createDummiesWithDateAndRelatedDummy($resource, $relatedResource, 2);

        $response = self::createClient()->request('GET', '/dummies?relatedDummy.dummyDate[after]=2015-04-28', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(0, $response->toArray()['hydra:totalItems']);
    }

    public function testCollectionFilteredByDateThatIsNotDatetime(): void
    {
        $resource = $this->dummyDateClass();
        $this->recreateSchema([$resource]);
        $this->createDummyDates($resource, 30);

        $response = self::createClient()->request('GET', '/dummy_dates?dummyDate[after]=2015-04-28', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $response->toArray()['hydra:totalItems']);
    }

    public function testCollectionFilteredByDateIncludeNullAfter(): void
    {
        $resource = $this->dummyDateClass();
        $this->recreateSchema([$resource]);
        $this->createDummyDates($resource, 3, 'dateIncludeNullAfter');

        $response = self::createClient()->request('GET', '/dummy_dates?dateIncludeNullAfter[after]=2015-04-02', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(2, $data['hydra:totalItems']);
        $this->assertSame('2015-04-02T00:00:00+00:00', $data['hydra:member'][0]['dateIncludeNullAfter']);
        $this->assertNull($data['hydra:member'][1]['dateIncludeNullAfter']);

        $response = self::createClient()->request('GET', '/dummy_dates?dateIncludeNullAfter[before]=2015-04-02', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(2, $data['hydra:totalItems']);
        $this->assertSame('2015-04-01T00:00:00+00:00', $data['hydra:member'][0]['dateIncludeNullAfter']);
        $this->assertSame('2015-04-02T00:00:00+00:00', $data['hydra:member'][1]['dateIncludeNullAfter']);
    }

    public function testCollectionFilteredByDateIncludeNullBefore(): void
    {
        $resource = $this->dummyDateClass();
        $this->recreateSchema([$resource]);
        $this->createDummyDates($resource, 3, 'dateIncludeNullBefore');

        $response = self::createClient()->request('GET', '/dummy_dates?dateIncludeNullBefore[before]=2015-04-01', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(2, $data['hydra:totalItems']);
        $this->assertSame('2015-04-01T00:00:00+00:00', $data['hydra:member'][0]['dateIncludeNullBefore']);
        $this->assertNull($data['hydra:member'][1]['dateIncludeNullBefore']);

        $response = self::createClient()->request('GET', '/dummy_dates?dateIncludeNullBefore[after]=2015-04-01', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(2, $data['hydra:totalItems']);
        $this->assertSame('2015-04-01T00:00:00+00:00', $data['hydra:member'][0]['dateIncludeNullBefore']);
        $this->assertSame('2015-04-02T00:00:00+00:00', $data['hydra:member'][1]['dateIncludeNullBefore']);
    }

    public function testCollectionFilteredByDateIncludeNullBeforeAndAfter(): void
    {
        $resource = $this->dummyDateClass();
        $this->recreateSchema([$resource]);
        $this->createDummyDates($resource, 3, 'dateIncludeNullBeforeAndAfter');

        $response = self::createClient()->request('GET', '/dummy_dates?dateIncludeNullBeforeAndAfter[before]=2015-04-01', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(2, $data['hydra:totalItems']);
        $this->assertSame('2015-04-01T00:00:00+00:00', $data['hydra:member'][0]['dateIncludeNullBeforeAndAfter']);
        $this->assertNull($data['hydra:member'][1]['dateIncludeNullBeforeAndAfter']);

        $response = self::createClient()->request('GET', '/dummy_dates?dateIncludeNullBeforeAndAfter[after]=2015-04-02', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(2, $data['hydra:totalItems']);
        $this->assertSame('2015-04-02T00:00:00+00:00', $data['hydra:member'][0]['dateIncludeNullBeforeAndAfter']);
        $this->assertNull($data['hydra:member'][1]['dateIncludeNullBeforeAndAfter']);
    }

    public function testCollectionFilteredByImmutableDate(): void
    {
        $resource = $this->isMongoDB() ? DummyImmutableDateDocument::class : DummyImmutableDate::class;
        $this->recreateSchema([$resource]);
        $manager = $this->getManager();
        for ($i = 1; $i <= 30; ++$i) {
            $dummy = new $resource();
            $dummy->dummyDate = new \DateTimeImmutable(\sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));
            $manager->persist($dummy);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/dummy_immutable_dates?dummyDate[after]=2015-04-28', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(3, $response->toArray()['hydra:totalItems']);
    }

    public function testCollectionFilteredByEmbeddedDate(): void
    {
        $embeddedClass = $this->isMongoDB() ? EmbeddedDummyDocument::class : EmbeddedDummy::class;
        $embeddableClass = $this->isMongoDB() ? EmbeddableDummyDocument::class : EmbeddableDummy::class;
        $this->recreateSchema([$embeddedClass]);

        $manager = $this->getManager();
        for ($i = 1; $i <= 29; ++$i) {
            $date = new \DateTime(\sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));
            $embeddable = new $embeddableClass();
            $embeddable->setDummyName('Embeddable #'.$i);
            $embeddable->setDummyDate($date);

            $dummy = new $embeddedClass();
            $dummy->setName('Dummy #'.$i);
            $dummy->setEmbeddedDummy($embeddable);
            if (29 !== $i) {
                $dummy->setDummyDate($date);
            }
            $manager->persist($dummy);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/embedded_dummies?embeddedDummy.dummyDate[after]=2015-04-28', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(2, $data['hydra:member']);
        $ids = array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']);
        sort($ids);
        $this->assertSame(['/embedded_dummies/28', '/embedded_dummies/29'], $ids);
    }

    public function testCollectionFilteredUsingNameConverter(): void
    {
        $resource = $this->isMongoDB() ? ConvertedDateDocument::class : ConvertedDate::class;
        $this->recreateSchema([$resource]);
        $manager = $this->getManager();
        for ($i = 1; $i <= 30; ++$i) {
            $entity = new $resource();
            $entity->nameConverted = new \DateTime(\sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));
            $manager->persist($entity);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/converted_dates?name_converted[strictly_after]=2015-04-28', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(2, $data['hydra:totalItems']);
        $ids = array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']);
        sort($ids);
        $this->assertSame(['/converted_dates/29', '/converted_dates/30'], $ids);
        foreach ($data['hydra:member'] as $member) {
            $this->assertSame('ConvertedDate', $member['@type']);
            $this->assertIsString($member['name_converted']);
        }

        $this->assertSame('hydra:IriTemplate', $data['hydra:search']['@type']);
        $this->assertSame('BasicRepresentation', $data['hydra:search']['hydra:variableRepresentation']);
        $variables = array_map(static fn (array $m): string => $m['variable'], $data['hydra:search']['hydra:mapping']);
        sort($variables);
        $this->assertSame([
            'name_converted[after]',
            'name_converted[before]',
            'name_converted[strictly_after]',
            'name_converted[strictly_before]',
        ], $variables);
        foreach ($data['hydra:search']['hydra:mapping'] as $mapping) {
            $this->assertSame('IriTemplateMapping', $mapping['@type']);
            $this->assertSame('name_converted', $mapping['property']);
        }
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
     * @return class-string
     */
    private function dummyDateClass(): string
    {
        return $this->isMongoDB() ? DummyDateDocument::class : DummyDate::class;
    }

    /**
     * @param class-string $resource
     */
    private function createDummiesWithDate(string $resource, int $nb): void
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new $resource();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            if ($nb !== $i) {
                $dummy->setDummyDate(new \DateTime(\sprintf('2015-04-%d', $i), new \DateTimeZone('UTC')));
            }
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    /**
     * @param class-string $resource
     * @param class-string $relatedResource
     */
    private function createDummiesWithDateAndRelatedDummy(string $resource, string $relatedResource, int $nb): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(\sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));
            $relatedDummy = new $relatedResource();
            $relatedDummy->setName('RelatedDummy #'.$i);
            $relatedDummy->setDummyDate($date);

            $dummy = new $resource();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setRelatedDummy($relatedDummy);
            if ($nb !== $i) {
                $dummy->setDummyDate($date);
            }
            $manager->persist($relatedDummy);
            $manager->persist($dummy);
        }
        $manager->flush();
    }

    /**
     * @param class-string $resource
     */
    private function createDummyDates(string $resource, int $nb, ?string $nullableProperty = null): void
    {
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $date = new \DateTime(\sprintf('2015-04-%d', $i), new \DateTimeZone('UTC'));
            $dummy = new $resource();
            $dummy->dummyDate = $date;
            if ($nullableProperty) {
                $dummy->{$nullableProperty} = 0 === $i % 3 ? null : $date;
            }
            $manager->persist($dummy);
        }
        $manager->flush();
    }
}
