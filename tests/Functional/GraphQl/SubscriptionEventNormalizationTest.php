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

use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SubscriptionEventResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use GraphQL\Type\Definition\ResolveInfo;
use PHPUnit\Framework\Attributes\DataProvider;

final class SubscriptionEventNormalizationTest extends ApiTestCase
{
    use SetupClassResourcesTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [SubscriptionEventResource::class];
    }

    #[DataProvider('operationNames')]
    public function testEventSerializationDoesNotDependOnOperationName(string $name): void
    {
        $payload = $this->normalize(new Subscription(name: $name));

        $this->assertEquals([
            'subscriptionEventResource' => [
                'id' => '/subscription_event_resources/1',
                '_id' => 1,
                'name' => 'Parent',
                'children' => [
                    'collection' => [
                        ['id' => '/subscription_event_resources/2', '_id' => 2, 'name' => 'Child'],
                    ],
                    'paginationInfo' => ['totalCount' => 1],
                ],
            ],
            'clientSubscriptionId' => null,
        ], $payload);
    }

    public static function operationNames(): iterable
    {
        yield 'existing internal name' => ['mercure_subscription'];
        yield 'different name' => ['renamed_event'];
    }

    #[DataProvider('operationNames')]
    public function testInitialSubscriptionResponseLeavesFieldsForGraphQlResolvers(string $name): void
    {
        $payload = $this->normalize(new Subscription(name: $name), initialResponse: true);
        $this->assertSame(1, $payload['subscriptionEventResource']['id']);
        $this->assertArrayNotHasKey('_id', $payload['subscriptionEventResource']);
        $this->assertSame([], $payload['subscriptionEventResource']['children']);
        $empty = $this->normalize(new Subscription(name: $name), initialResponse: true, childCount: 0);
        $this->assertSame([], $empty['subscriptionEventResource']['children']);
    }

    #[DataProvider('relatedCollections')]
    public function testRelatedCollectionUsesConfiguredLimit(int $count, bool $collectionSubscription): void
    {
        $operation = $collectionSubscription ? new SubscriptionCollection(name: 'watch') : new Subscription(name: 'watch');
        $payload = $this->normalize($operation, childCount: $count, relationFields: [
            'collection' => ['id' => true, '_id' => true, 'name' => true],
            'paginationInfo' => ['hasNextPage' => true, 'itemsPerPage' => true, 'lastPage' => true, 'totalCount' => true],
        ]);
        $expected = [];
        for ($i = 0; $i < min($count, 3); ++$i) {
            $expected[] = ['id' => '/subscription_event_resources/'.($i + 2), 'name' => 'Child', '_id' => $i + 2];
        }
        // The test application configures pagination_items_per_page to 3.
        $itemsPerPage = 3;
        $this->assertSame([
            'collection' => $expected,
            'paginationInfo' => [
                'hasNextPage' => $count > $itemsPerPage,
                'itemsPerPage' => $itemsPerPage,
                'lastPage' => (int) ceil($count / $itemsPerPage),
                'totalCount' => $count,
            ],
        ], $payload['subscriptionEventResource']['children']);
    }

    public static function relatedCollections(): iterable
    {
        foreach ([false, true] as $collection) {
            foreach ([0, 2, 3, 12] as $count) {
                yield ($collection ? 'collection' : 'item').' with '.$count.' relations' => [$count, $collection];
            }
        }
    }

    #[DataProvider('relationSelections')]
    public function testRelatedCollectionIncludesOnlySelectedFields(int $count, array $fields, array $expected): void
    {
        $payload = $this->normalize(new Subscription(name: 'watch'), childCount: $count, relationFields: $fields);
        $this->assertSame($expected, $payload['subscriptionEventResource']['children']);
    }

    public static function relationSelections(): iterable
    {
        foreach ([0, 1] as $count) {
            yield 'collection only, '.$count => [$count, ['collection' => ['name' => true]], ['collection' => $count ? [['name' => 'Child']] : []]];
            yield 'total count only, '.$count => [$count, ['paginationInfo' => ['totalCount' => true]], ['paginationInfo' => ['totalCount' => $count]]];
        }
    }

    private function normalize(Subscription $operation, bool $initialResponse = false, int $childCount = 1, ?array $relationFields = null): array
    {
        $parent = new SubscriptionEventResource();
        $parent->id = 1;
        $parent->name = 'Parent';
        for ($i = 0; $i < $childCount; ++$i) {
            $child = new SubscriptionEventResource();
            $child->id = $i + 2;
            $child->name = 'Child';
            $parent->children->add($child);
        }

        $operation = $operation->withClass(SubscriptionEventResource::class)->withShortName('SubscriptionEventResource');

        $fields = [
            'subscriptionEventResource' => [
                'id' => true,
                '_id' => true,
                'name' => true,
                'children' => $relationFields ?? [
                    'collection' => ['id' => true, '_id' => true, 'name' => true],
                    'paginationInfo' => ['totalCount' => true],
                ],
            ],
        ];

        if ($initialResponse) {
            $info = $this->createStub(ResolveInfo::class);
            $info->method('getFieldSelection')->willReturn($fields);
            $context = ['info' => $info];
        } else {
            $context = ['fields' => $fields];
        }

        return self::getContainer()->get('api_platform.graphql.state_processor.normalize')->process($parent, $operation, [], $context);
    }
}
