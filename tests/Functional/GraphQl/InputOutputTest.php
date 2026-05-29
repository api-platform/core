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
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoInputOutput as DummyDtoInputOutputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoNoInput as DummyDtoNoInputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\DummyDtoNoOutput as DummyDtoNoOutputDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Document\RelatedDummy as RelatedDummyDocument;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoInputOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoNoInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDtoNoOutput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\MessengerWithInput;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Tests\RecreateSchemaTrait;
use ApiPlatform\Tests\SetupClassResourcesTrait;

final class InputOutputTest extends ApiTestCase
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
        return [
            DummyDtoInputOutput::class,
            DummyDtoNoOutput::class,
            DummyDtoNoInput::class,
            MessengerWithInput::class,
            RelatedDummy::class,
        ];
    }

    public function testRetrieveOutputAfterRestCreation(): void
    {
        $this->recreateSchema($this->resolveResources([
            DummyDtoInputOutput::class => DummyDtoInputOutputDocument::class,
            RelatedDummy::class => RelatedDummyDocument::class,
        ]));
        $this->seedRelatedDummy();

        $client = self::createClient();
        $client->request('POST', '/dummy_dto_input_outputs', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => ['foo' => 'test', 'bar' => 1, 'relatedDummies' => ['/related_dummies/1']],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $response = $this->executeGraphQl(<<<'QUERY'
            {
              dummyDtoInputOutput(id: "/dummy_dto_input_outputs/1") {
                _id, id, baz,
                relatedDummies {
                  edges {
                    node {
                      name
                    }
                  }
                }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'data' => [
                'dummyDtoInputOutput' => [
                    '_id' => 1,
                    'id' => '/dummy_dto_input_outputs/1',
                    'baz' => 1,
                    'relatedDummies' => [
                        'edges' => [
                            ['node' => ['name' => 'RelatedDummy with friends']],
                        ],
                    ],
                ],
            ],
        ], $response->toArray());
    }

    public function testCreateItemWithCustomInputAndOutput(): void
    {
        $this->recreateSchema($this->resolveResources([DummyDtoInputOutput::class => DummyDtoInputOutputDocument::class]));

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createDummyDtoInputOutput(input: {foo: "A foo", bar: 4, clientMutationId: "myId"}) {
                dummyDtoInputOutput {
                  baz,
                  bat
                }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'data' => [
                'createDummyDtoInputOutput' => [
                    'dummyDtoInputOutput' => ['baz' => 4, 'bat' => 'A foo'],
                    'clientMutationId' => 'myId',
                ],
            ],
        ], $response->toArray());
    }

    public function testCreateItemWithDisabledOutputClassFailsToQueryFields(): void
    {
        $this->recreateSchema($this->resolveResources([DummyDtoNoOutput::class => DummyDtoNoOutputDocument::class]));
        $this->seedDummyDtoNoOutput(2);

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createDummyDtoNoOutput(input: {foo: "A new one", bar: 3, clientMutationId: "myId"}) {
                dummyDtoNoOutput {
                  id
                }
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertSame('Cannot query field "id" on type "DummyDtoNoOutput".', $data['errors'][0]['message']);
        $this->assertSame(4, $data['errors'][0]['locations'][0]['line']);
        $this->assertSame(7, $data['errors'][0]['locations'][0]['column']);
    }

    public function testCreateItemWithDisabledInputClassRejectsUndefinedFields(): void
    {
        $this->recreateSchema($this->resolveResources([DummyDtoNoInput::class => DummyDtoNoInputDocument::class]));

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createDummyDtoNoInput(input: {lorem: "A new one", ipsum: 3, clientMutationId: "myId"}) {
                clientMutationId
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray(false);
        $this->assertMatchesRegularExpression(
            '/^Field "lorem" is not defined by type "?createDummyDtoNoInputInput"?\.$/',
            $data['errors'][0]['message'],
        );
        $this->assertMatchesRegularExpression(
            '/^Field "ipsum" is not defined by type "?createDummyDtoNoInputInput"?\.$/',
            $data['errors'][1]['message'],
        );
    }

    public function testMessengerWithInputReturnsSynchronousResult(): void
    {
        // MessengerWithInput is not a Doctrine resource — nothing to recreate.

        $response = $this->executeGraphQl(<<<'QUERY'
            mutation {
              createMessengerWithInput(input: {var: "test"}) {
                messengerWithInput { id, name }
              }
            }
            QUERY);

        $this->assertResponseIsSuccessful();
        $this->assertSame([
            'data' => [
                'createMessengerWithInput' => [
                    'messengerWithInput' => [
                        'id' => '/messenger_with_inputs/1',
                        'name' => 'test',
                    ],
                ],
            ],
        ], $response->toArray());
    }

    /**
     * @param array<class-string, class-string> $map
     *
     * @return list<class-string>
     */
    private function resolveResources(array $map): array
    {
        $resolved = [];
        foreach ($map as $entity => $document) {
            $resolved[] = $this->isMongoDB() ? $document : $entity;
        }

        return $resolved;
    }

    private function seedRelatedDummy(): void
    {
        $resourceClass = $this->isMongoDB() ? RelatedDummyDocument::class : RelatedDummy::class;
        $manager = $this->getManager();
        $related = new $resourceClass();
        $related->setName('RelatedDummy with friends');
        $manager->persist($related);
        $manager->flush();
        $manager->clear();
    }

    private function seedDummyDtoNoOutput(int $count): void
    {
        $resourceClass = $this->isMongoDB() ? DummyDtoNoOutputDocument::class : DummyDtoNoOutput::class;
        $manager = $this->getManager();
        for ($i = 1; $i <= $count; ++$i) {
            $dto = new $resourceClass();
            $dto->lorem = 'DummyDtoNoOutput foo #'.$i;
            $dto->ipsum = (string) ($i / 3);
            $manager->persist($dto);
        }
        $manager->flush();
    }
}
