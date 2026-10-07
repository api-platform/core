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

use ApiPlatform\Metadata\Resource\Factory\AttributesResourceMetadataCollectionFactory;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\AutomaticSubscriptionResource;
use ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\DefaultSubscriptionResource;
use ApiPlatform\Tests\SetupClassResourcesTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TraceableAdapter;

final class SubscriptionNamingTest extends ApiTestCase
{
    use SetupClassResourcesTrait;
    use SubscriptionPublicationTrait;

    protected static ?bool $alwaysBootKernel = false;

    public static function getResources(): array
    {
        return [DefaultSubscriptionResource::class, AutomaticSubscriptionResource::class];
    }

    public function testSubscriptionNamesAndDescriptions(): void
    {
        $response = self::createClient()->request('POST', '/graphql', ['json' => [
            'query' => '{ __schema { subscriptionType { fields { name description } } } }',
        ]]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json, \JSON_THROW_ON_ERROR));
        $fields = array_column($json['data']['__schema']['subscriptionType']['fields'], 'description', 'name');

        $this->assertSame([
            'itemDefaultSubscriptionResourceSubscribe' => 'Subscribes to updates and deletion of an individual DefaultSubscriptionResource.',
            'updateDefaultSubscriptionResourceSubscribe' => 'Subscribes to updates and deletion of an individual DefaultSubscriptionResource.',
            'collectionDefaultSubscriptionResourceSubscribe' => 'Subscribes to creation, updates and deletion of DefaultSubscriptionResource resources.',
            'watchDefaultSubscriptionResourceSubscribe' => 'Custom item description.',
            'watchCollectionDefaultSubscriptionResourceSubscribe' => 'Custom collection description.',
            'updateAutomaticSubscriptionResourceSubscribe' => 'Subscribes to updates and deletion of an individual AutomaticSubscriptionResource.',
            'itemOperationSubscriptionResourceSubscribe' => 'Subscribes to updates and deletion of an individual OperationSubscriptionResource.',
            'updateOperationSubscriptionResourceSubscribe' => 'Subscribes to updates and deletion of an individual OperationSubscriptionResource.',
        ], $fields);
    }

    #[DataProvider('subscriptionOperations')]
    public function testDefaultSubscriptionsRegisterAndReceiveEvents(string $name, string $event): void
    {
        $client = self::createClient();
        $client->disableReboot();
        self::getContainer()->set('api_platform.graphql.cache.subscription', new TraceableAdapter(new ArrayAdapter()));
        $field = $name.'DefaultSubscriptionResourceSubscribe';
        $response = $client->request('POST', '/graphql', ['json' => [
            'query' => \sprintf('subscription { %s(input: {id: "/default_subscription_resources/1"}) { defaultSubscriptionResource { id _id } mercureUrl } }', $field),
        ]]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray(false);
        $this->assertArrayNotHasKey('errors', $json, json_encode($json, \JSON_THROW_ON_ERROR));
        parse_str(parse_url($json['data'][$field]['mercureUrl'], \PHP_URL_QUERY), $query);

        $object = new DefaultSubscriptionResource();
        $object->id = 'collection' === $name ? 2 : 1;
        $iri = '/default_subscription_resources/'.$object->id;
        $data = ['defaultSubscriptionResource' => ['id' => $iri, '_id' => $object->id]];
        if ('delete' === $event) {
            $object = (object) [
                'resourceClass' => DefaultSubscriptionResource::class,
                'id' => $iri,
                'iri' => 'http://example.com'.$iri,
                'type' => 'DefaultSubscriptionResource',
                'private' => [],
            ];
            $data = ['type' => 'delete', 'payload' => ['id' => $iri, 'iri' => $object->iri, 'type' => $object->type]];
        }

        $payloads = $this->publishSubscriptions($object, $event);
        $this->assertCount(1, $payloads);
        [$id, $payload] = $payloads[0];
        $this->assertSame($query['topic'], self::getContainer()->get('api_platform.graphql.subscription.mercure_iri_generator')->generateTopicIri($id));
        $this->assertSame($data, $payload);
    }

    public static function subscriptionOperations(): iterable
    {
        yield 'neutral item name receives deletion' => ['item', 'delete'];
        yield 'explicit legacy item name receives deletion' => ['update', 'delete'];
        yield 'collection creation' => ['collection', 'create'];
        yield 'collection update' => ['collection', 'update'];
        yield 'collection deletion' => ['collection', 'delete'];
    }

    public function testLegacyAutomaticSubscriptionTriggersDeprecation(): void
    {
        $this->expectUserDeprecationMessage('Since api-platform/core 4.4: Using the implicit "update_subscription" GraphQL subscription name is deprecated. Set "defaults.extra_properties.legacy_graphql_subscription_names" to false to use "item", or explicitly set the subscription name to "update" to preserve the existing GraphQL field.');
        $factory = new AttributesResourceMetadataCollectionFactory(graphQlEnabled: true);
        $operation = $factory->create(AutomaticSubscriptionResource::class)[0]->getGraphQlOperations()['update_subscription'];
        $this->assertSame('update_subscription', $operation->getName());
    }

    public function testGlobalFlagChangesAutomaticSubscriptionName(): void
    {
        $factory = new AttributesResourceMetadataCollectionFactory(defaults: ['extra_properties' => ['legacy_graphql_subscription_names' => false]], graphQlEnabled: true);
        $operations = $factory->create(AutomaticSubscriptionResource::class)[0]->getGraphQlOperations();
        $this->assertArrayHasKey('item', $operations);
        $this->assertArrayNotHasKey('update_subscription', $operations);
    }

    public function testResourceFlagOverridesGlobalDefault(): void
    {
        $factory = new AttributesResourceMetadataCollectionFactory(defaults: ['extra_properties' => ['legacy_graphql_subscription_names' => true]], graphQlEnabled: true);
        $operations = $factory->create(DefaultSubscriptionResource::class)[0]->getGraphQlOperations();
        $this->assertArrayHasKey('item', $operations);
        $this->assertArrayHasKey('update', $operations);
        $this->assertArrayHasKey('collection', $operations);
        $this->assertArrayNotHasKey('update_subscription', $operations);
    }

    public function testOperationFlagAndExplicitNameOverrideResourceDefault(): void
    {
        $factory = new AttributesResourceMetadataCollectionFactory(defaults: ['extra_properties' => ['legacy_graphql_subscription_names' => false]], graphQlEnabled: true);
        $operations = $factory->create(AutomaticSubscriptionResource::class)[1]->getGraphQlOperations();
        $this->assertArrayHasKey('item', $operations);
        $this->assertArrayHasKey('update_subscription', $operations);
        $this->assertFalse($operations['item']->getExtraProperties()['legacy_graphql_subscription_names']);
        $this->assertTrue($operations['update_subscription']->getExtraProperties()['legacy_graphql_subscription_names']);
    }
}
