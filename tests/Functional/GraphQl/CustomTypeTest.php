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

namespace ApiPlatform\Tests\Functional\GraphQl;

use ApiPlatform\GraphQl\Test\GraphQlTestTrait;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\Dummy as DummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class CustomTypeTest extends ApiTestCase
{
    use GraphQlTestTrait;
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

    protected function setUp(): void
    {
        $resource = $this->isMongoDB() ? DummyDocument::class : Dummy::class;
        $this->recreateSchema([$resource]);
        $this->seedDummies($resource);
    }

    public function testQueryFieldWithCustomType(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummy(id: "/dummies/1") {
                dummyDate
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame('2015-04-01', $response->toArray()['data']['dummy']['dummyDate']);
    }

    public function testMutationInputWithCustomType(): void
    {
        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              updateDummy(input: {id: "/dummies/1", dummyDate: "2019-05-24T00:00:00+00:00"}) {
                dummy {
                  dummyDate
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame('2019-05-24', $response->toArray()['data']['updateDummy']['dummy']['dummyDate']);
    }

    public function testMutationVariableWithCustomType(): void
    {
        $response = $this->executeGraphQl(
            <<<'QUERY'
                mutation UpdateDummyDate($itemId: ID!, $itemDate: DateTime!) {
                  updateDummy(input: {id: $itemId, dummyDate: $itemDate}) {
                    dummy {
                      dummyDate
                    }
                  }
                }
                QUERY,
            ['itemId' => '/dummies/1', 'itemDate' => '2017-11-14T00:00:00+00:00'],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame('2017-11-14', $response->toArray()['data']['updateDummy']['dummy']['dummyDate']);
    }

    public function testMutationVariableWithCustomTypeAndBadValue(): void
    {
        $response = $this->executeGraphQl(
            <<<'QUERY'
                mutation UpdateDummyDate($itemId: ID!, $itemDate: DateTime!) {
                  updateDummy(input: {id: $itemId, dummyDate: $itemDate}) {
                    dummy {
                      dummyDate
                    }
                  }
                }
                QUERY,
            ['itemId' => '/dummies/1', 'itemDate' => 'bad date'],
        );

        $this->assertResponseIsSuccessful();
        $message = $response->toArray(false)['errors'][0]['message'] ?? '';
        $this->assertStringContainsString('Variable "$itemDate" got invalid value "bad date";', $message);
        $this->assertStringContainsString('DateTime cannot represent non date value: "bad date"', $message);
    }

    private function seedDummies(string $resourceClass): void
    {
        $manager = $this->getManager();
        $dummy1 = new $resourceClass();
        $dummy1->setName('Dummy #1');
        $dummy1->setAlias('Alias #1');
        $dummy1->setDescription('Smart dummy.');
        $dummy1->setDummyDate(new \DateTime('2015-04-01', new \DateTimeZone('UTC')));
        $manager->persist($dummy1);

        $dummy2 = new $resourceClass();
        $dummy2->setName('Dummy #2');
        $dummy2->setAlias('Alias #0');
        $dummy2->setDescription('Not so smart dummy.');
        $manager->persist($dummy2);

        $manager->flush();
    }
}
