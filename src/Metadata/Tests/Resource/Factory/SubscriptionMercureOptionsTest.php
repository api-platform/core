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

namespace ApiPlatform\Metadata\Tests\Resource\Factory;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Extractor\ResourceExtractorInterface;
use ApiPlatform\Metadata\GraphQl\Query;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\GraphQl\SubscriptionCollection;
use ApiPlatform\Metadata\Resource\Factory\ExtractorResourceMetadataCollectionFactory;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SubscriptionMercureOptionsTest extends TestCase
{
    #[DataProvider('invalidOptions')]
    public function testInvalidOptionsAreRejected(string $operationClass, array $resource, array $operation, array $defaults): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"private_fields" requires "mercure.private" to be true.');

        $this->createMetadata($operationClass, $resource, $operation, $defaults);
    }

    public static function invalidOptions(): iterable
    {
        foreach ([Subscription::class, SubscriptionCollection::class] as $class) {
            yield $class.' missing private' => [$class, [], ['mercure' => ['private_fields' => ['tenant']]], []];
            yield $class.' private false' => [$class, [], ['mercure' => ['private' => false, 'private_fields' => ['tenant']]], []];
            yield $class.' inherited from resource' => [$class, ['mercure' => ['private_fields' => ['tenant']]], [], []];
            yield $class.' inherited from defaults' => [$class, [], [], ['mercure' => ['private_fields' => ['tenant']]]];
            yield $class.' operation overrides valid resource options' => [$class, ['mercure' => ['private' => true, 'private_fields' => ['tenant']]], ['mercure' => ['private_fields' => ['tenant']]], []];
        }
    }

    #[DataProvider('validOptions')]
    public function testValidOptionsArePreserved(string $operationClass, array $resource, array $operation, array $defaults, array|bool|null $expected): void
    {
        $metadata = $this->createMetadata($operationClass, $resource, $operation, $defaults);
        $this->assertSame($expected, $metadata[0]->getGraphQlOperations()['watch']->getMercure());
    }

    public static function validOptions(): iterable
    {
        foreach ([Subscription::class, SubscriptionCollection::class] as $class) {
            yield $class.' no Mercure' => [$class, [], [], [], null];
            yield $class.' public Mercure' => [$class, [], ['mercure' => true], [], true];
            yield $class.' empty private fields' => [$class, [], ['mercure' => ['private_fields' => []]], [], ['private_fields' => []]];
            yield $class.' private without partition' => [$class, [], ['mercure' => ['private' => true]], [], ['private' => true]];
            $options = ['private' => true, 'private_fields' => ['tenant']];
            yield $class.' private partition' => [$class, [], ['mercure' => $options], [], $options];
            yield $class.' inherited from resource' => [$class, ['mercure' => $options], [], [], $options];
            yield $class.' inherited from defaults' => [$class, [], [], ['mercure' => $options], $options];
            yield $class.' operation overrides invalid resource options' => [$class, ['mercure' => ['private_fields' => ['tenant']]], ['mercure' => $options], [], $options];
            yield $class.' operation disables inherited Mercure' => [$class, ['mercure' => ['private_fields' => ['tenant']]], ['mercure' => false], [], false];
        }

        yield 'query is not a subscription' => [Query::class, [], ['mercure' => ['private_fields' => ['tenant']]], [], ['private_fields' => ['tenant']]];
        yield 'resource without subscriptions' => [Query::class, ['mercure' => ['private_fields' => ['tenant']]], [], [], ['private_fields' => ['tenant']]];
    }

    public function testAutomaticallyGeneratedSubscriptionIsValidated(): void
    {
        $extractor = $this->createStub(ResourceExtractorInterface::class);
        $extractor->method('getResources')->willReturn([\stdClass::class => [['mercure' => ['private_fields' => ['tenant']]]]]);
        $factory = new ExtractorResourceMetadataCollectionFactory($extractor, defaults: ['extra_properties' => ['legacy_graphql_subscription_names' => false]], graphQlEnabled: true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"private_fields" requires "mercure.private" to be true.');
        $factory->create(\stdClass::class);
    }

    private function createMetadata(string $operationClass, array $resource, array $operation, array $defaults): ResourceMetadataCollection
    {
        $extractor = $this->createStub(ResourceExtractorInterface::class);
        $extractor->method('getResources')->willReturn([\stdClass::class => [
            $resource + ['graphQlOperations' => [['class' => $operationClass, 'name' => 'watch'] + $operation]],
        ]]);

        return (new ExtractorResourceMetadataCollectionFactory($extractor, defaults: $defaults, graphQlEnabled: true))->create(\stdClass::class);
    }
}
