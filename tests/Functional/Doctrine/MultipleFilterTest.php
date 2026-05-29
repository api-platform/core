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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class MultipleFilterTest extends ApiTestCase
{
    use RecreateSchemaTrait;
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    /**
     * @return class-string[]
     */
    public static function getResources(): array
    {
        return [Dummy::class];
    }

    public function testCollectionFilteredByDateAndBoolean(): void
    {
        $resource = $this->isMongoDB() ? DummyDocument::class : Dummy::class;
        $this->recreateSchema([$resource]);
        $this->createDummies($resource, 30, true);
        $this->createDummies($resource, 20, false);

        $response = self::createClient()->request('GET', '/dummies?dummyDate[after]=2015-04-28&dummyBoolean=1', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/ld+json; charset=utf-8');

        $data = $response->toArray();
        $this->assertSame('/contexts/Dummy', $data['@context']);
        $this->assertSame('/dummies', $data['@id']);
        $this->assertSame('hydra:Collection', $data['@type']);
        $this->assertCount(2, $data['hydra:member']);

        $ids = array_map(static fn (array $item): string => $item['@id'], $data['hydra:member']);
        sort($ids);
        $this->assertSame(['/dummies/28', '/dummies/29'], $ids);

        $this->assertSame('hydra:PartialCollectionView', $data['hydra:view']['@type']);
        $this->assertSame('/dummies?dummyBoolean=1&dummyDate%5Bafter%5D=2015-04-28', $data['hydra:view']['@id']);
    }

    /**
     * @param class-string $resource
     */
    private function createDummies(string $resource, int $nb, bool $bool): void
    {
        $descriptions = ['Smart dummy.', 'Not so smart dummy.'];
        $manager = $this->getManager();

        for ($i = 1; $i <= $nb; ++$i) {
            $dummy = new $resource();
            $dummy->setName('Dummy #'.$i);
            $dummy->setAlias('Alias #'.($nb - $i));
            $dummy->setDescription($descriptions[($i - 1) % 2]);
            $dummy->setDummyBoolean($bool);

            if ($nb !== $i) {
                $dummy->setDummyDate(new \DateTime(\sprintf('2015-04-%d', $i), new \DateTimeZone('UTC')));
            }

            $manager->persist($dummy);
        }

        $manager->flush();
    }
}
