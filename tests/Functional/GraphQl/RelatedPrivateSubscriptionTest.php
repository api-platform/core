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
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\RelatedPrivateSubscriptionResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\SubscriptionPartitionTenant;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;

final class RelatedPrivateSubscriptionTest extends ApiTestCase
{
    use SetupClassResourcesTrait;
    use SubscriptionPublicationTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [RelatedPrivateSubscriptionResource::class, SubscriptionPartitionTenant::class];
    }

    public function testRelatedResourceIdentifiersPartitionRegistrationAndPublishing(): void
    {
        $container = self::getContainer();
        $container->set('api_platform.graphql.cache.subscription', new TraceableAdapter(new ArrayAdapter()));
        $manager = $container->get('api_platform.graphql.subscription.subscription_manager');
        $metadata = $container->get('api_platform.metadata.resource.metadata_collection_factory')->create(RelatedPrivateSubscriptionResource::class);
        $operation = $metadata->getOperation('watch', forceGraphQl: true);
        $this->assertInstanceOf(Subscription::class, $operation);
        $info = $this->createStub(ResolveInfo::class);
        $info->method('getFieldSelection')->willReturn(['relatedPrivateSubscriptionResource' => ['name' => true]]);

        $ids = [];
        $objects = [];
        foreach (['eu', 'us'] as $region) {
            $object = new RelatedPrivateSubscriptionResource();
            $object->tenant = new SubscriptionPartitionTenant();
            $object->tenant->region = $region;
            $objects[] = $object;
            $ids[] = $manager->retrieveSubscriptionId([
                'args' => ['input' => ['id' => '/related_private_subscription_resources/1']],
                'info' => $info,
                'graphql_context' => ['previous_object' => $object],
            ], ['relatedPrivateSubscriptionResource' => ['name' => 'Initial']], $operation);
        }
        $this->assertNotNull($ids[0]);
        $this->assertNotNull($ids[1]);
        $this->assertNotSame($ids[0], $ids[1]);

        foreach ($objects as $index => $object) {
            $object->name = 'Changed';
            $this->assertSame([
                [$ids[$index], ['relatedPrivateSubscriptionResource' => ['name' => 'Changed']]],
            ], $this->publishSubscriptions($object));
        }
    }
}
