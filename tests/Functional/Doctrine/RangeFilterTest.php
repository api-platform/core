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
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedInteger;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\TestWith;

final class RangeFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Dummy::class, ConvertedInteger::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $resource = $this->dummyClass();
        $this->recreateSchema([$resource]);
        $this->createDummiesWithPrice($resource, 30);
    }

    #[TestWith(['dummyPrice[between]=12.99..15.99', 15])]
    #[TestWith(['dummyPrice[between]=12.99..12.99', 8])]
    #[TestWith(['dummyPrice[between]=9.99..12.99..15.99', 30])]
    #[TestWith(['dummyPrice[lt]=12.99', 8])]
    #[TestWith(['dummyPrice[lte]=12.99', 16])]
    #[TestWith(['dummyPrice[gt]=15.99', 7])]
    #[TestWith(['dummyPrice[gte]=15.99', 14])]
    #[TestWith(['dummyPrice[gt]=12.99&dummyPrice[lt]=19.99', 7])]
    #[TestWith(['dummyPrice[gt]=19.99', 0])]
    public function testRangeFilter(string $query, int $expectedTotal): void
    {
        $response = self::createClient()->request('GET', '/dummies?'.$query, [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json');
        $data = $response->toArray();
        $this->assertSame($expectedTotal, $data['hydra:totalItems']);
    }

    public function testCollectionFilteredUsingNameConverter(): void
    {
        $resource = $this->isMongoDB() ? ConvertedIntegerDocument::class : ConvertedInteger::class;
        $this->recreateSchema([$resource]);
        $manager = $this->getManager();
        for ($i = 1; $i <= 5; ++$i) {
            $entity = new $resource();
            $entity->nameConverted = $i;
            $manager->persist($entity);
        }
        $manager->flush();

        $response = self::createClient()->request('GET', '/converted_integers?name_converted[lte]=2', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(2, $data['hydra:totalItems']);
        $ids = array_map(static fn (array $i): string => $i['@id'], $data['hydra:member']);
        sort($ids);
        $this->assertSame(['/converted_integers/1', '/converted_integers/2'], $ids);
        foreach ($data['hydra:member'] as $member) {
            $this->assertSame('ConvertedInteger', $member['@type']);
            $this->assertIsInt($member['name_converted']);
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
     * @param class-string $resource
     */
    private function createDummiesWithPrice(string $resource, int $nb): void
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];
        $prices = ['9.99', '12.99', '15.99', '19.99'];
        $manager = $this->getManager();
        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new $resource();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->setDummyPrice($prices[($i - 1) % 4]);
            $manager->persist($dummy);
        }
        $manager->flush();
    }
}
