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
    }

    private function normalize(Subscription $operation, bool $initialResponse = false): array
    {
        $parent = new SubscriptionEventResource();
        $parent->id = 1;
        $parent->name = 'Parent';
        $child = new SubscriptionEventResource();
        $child->id = 2;
        $child->name = 'Child';
        $parent->children->add($child);

        $operation = $operation->withClass(SubscriptionEventResource::class)->withShortName('SubscriptionEventResource');

        $fields = [
            'subscriptionEventResource' => [
                'id' => true,
                '_id' => true,
                'name' => true,
                'children' => [
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
