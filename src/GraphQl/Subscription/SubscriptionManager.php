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

namespace ApiPlatform\GraphQl\Subscription;

use ApiPlatform\GraphQl\Resolver\Util\IdentifierTrait;
use ApiPlatform\GraphQl\Util\PropertyAccessorValueExtractor;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\GraphQl\Subscription;
use ApiPlatform\Metadata\IdentifiersExtractorInterface;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\SortTrait;
use ApiPlatform\State\ProcessorInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Manages all the queried subscriptions by creating their ID
 * and saving to a cache the information needed to publish updated data.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class SubscriptionManager implements SubscriptionManagerInterface
{
    use IdentifierTrait;
    use SortTrait;

    public function __construct(private readonly SubscriptionStore $store, private readonly SubscriptionIdentifierGeneratorInterface $subscriptionIdentifierGenerator, private readonly ProcessorInterface $normalizeProcessor, private readonly IriConverterInterface $iriConverter, private readonly ?IdentifiersExtractorInterface $identifiersExtractor = null, private readonly ?ResourceClassResolverInterface $resourceClassResolver = null)
    {
    }

    public function retrieveSubscriptionId(array $context, ?array $result, Subscription $operation): ?string
    {
        $iri = $this->getIdentifierFromOperation($operation, $context['args'] ?? []);
        if (empty($iri)) {
            return null;
        }

        /** @var ResolveInfo $info */
        $info = $context['info'];
        $fields = $info->getFieldSelection(\PHP_INT_MAX);
        $this->arrayRecursiveSort($fields, 'ksort');

        $privateFieldData = $this->getPrivateFieldData($operation, $context['graphql_context']['previous_object'] ?? null);
        $collection = $operation instanceof CollectionOperationInterface;
        $cacheKey = $this->getSubscriptionKey($iri, $operation, $privateFieldData);
        $operationKey = $this->getOperationKey($operation);
        unset($result['clientSubscriptionId']);

        $identity = $fields + ($collection ? ['__collection' => true] : []) + ['__subscription_scope' => $cacheKey, '__subscription_operation' => $operationKey];

        return $this->store->register(
            $cacheKey,
            $operationKey,
            $fields,
            $result,
            $collection,
            fn (): string => $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier($identity)
        );
    }

    /**
     * @param list<array{object: object, operation: Subscription}> $publications
     *
     * @return iterable<array{Subscription, SubscriptionUpdate}>
     */
    public function getUpdates(array $publications, string $type = 'update'): iterable
    {
        $groups = [];
        foreach ($publications as $publication) {
            ['object' => $object, 'operation' => $operation] = $publication;
            if ((!$operation instanceof CollectionOperationInterface && 'create' === $type) || false === $operation->getMercure()) {
                continue;
            }
            if ('delete' === $type) {
                $iri = $object->id;
                $private = $this->getDeletedPrivateFieldData($object, $operation);
            } else {
                // Collection registry keys do not include the item IRI.
                $iri = $operation instanceof CollectionOperationInterface ? null : $this->iriConverter->getIriFromResource($object);
                $private = $this->getPrivateFieldData($operation, $object);
            }
            $key = $this->getSubscriptionKey($iri, $operation, $private);
            $groups[$key][$this->getOperationKey($operation)] = $publication;
        }

        foreach ($groups as $key => $operations) {
            $collection = reset($operations)['operation'] instanceof CollectionOperationInterface;
            if ('delete' === $type) {
                $payloads = [];
                foreach ($operations as $name => ['object' => $object]) {
                    $payloads[$name] = ['type' => 'delete', 'payload' => ['id' => $object->id, 'iri' => $object->iri, 'type' => $object->type]];
                }
                foreach ($this->store->getDeleteUpdates($key, $collection, $payloads) as [$name, $update]) {
                    yield [$operations[$name]['operation'], $update];
                }

                continue;
            }

            $subscriptions = $this->store->getSubscriptions($key, $collection);
            foreach ($operations as $name => ['object' => $object, 'operation' => $operation]) {
                foreach ($subscriptions[$name] ?? [] as $subscription) {
                    $data = $this->normalizeProcessor->process($object, $operation, [], ['fields' => $subscription->fields]);
                    unset($data['clientSubscriptionId']);
                    $update = $this->store->prepareUpdate($subscription, $data);
                    if (null !== $update) {
                        yield [$operation, $update];
                    }
                }
            }
        }
    }

    public function acknowledge(SubscriptionUpdate $update): void
    {
        $this->store->acknowledge($update);
    }

    private function getOperationKey(Subscription $operation): string
    {
        return $operation->getShortName().':'.$operation->getName();
    }

    private function getSubscriptionKey(?string $iri, Subscription $operation, array $privateFieldData): string
    {
        $collection = $operation instanceof CollectionOperationInterface;

        ksort($privateFieldData);

        return 'graphql_subscription_'.hash('sha256', serialize([
            'resource' => $operation->getClass(),
            'collection' => $collection,
            'iri' => $collection ? null : $iri,
            'private' => $privateFieldData,
        ]));
    }

    /**
     * @return string[]
     */
    private function getPrivateFields(Subscription $operation): array
    {
        $options = $operation->getMercure() ?? false;

        return ($options['private'] ?? false) ? ($options['private_fields'] ?? []) : [];
    }

    /**
     * @return array<string, string>
     */
    private function getPrivateFieldData(Subscription $operation, ?object $object): array
    {
        $privateFields = $this->getPrivateFields($operation);
        if ([] === $privateFields) {
            return [];
        }
        if (null === $object) {
            throw new RuntimeException(\sprintf('Cannot resolve private fields for subscription "%s" without a resource object.', $operation->getName()));
        }

        $privateFieldData = [];
        foreach ($privateFields as $privateField) {
            $privateFieldData[$privateField] = PropertyAccessorValueExtractor::getValue($object, $privateField, $this->identifiersExtractor, $this->resourceClassResolver);
        }

        return $privateFieldData;
    }

    /** @return array<string, string> */
    private function getDeletedPrivateFieldData(object $object, Subscription $operation): array
    {
        $privateFieldData = [];
        foreach ($this->getPrivateFields($operation) as $privateField) {
            if (!\array_key_exists($privateField, $object->private)) {
                throw new RuntimeException(\sprintf('Private field "%s" is missing from the delete snapshot for subscription "%s".', $privateField, $operation->getName()));
            }
            $privateFieldData[$privateField] = $object->private[$privateField];
        }

        return $privateFieldData;
    }
}
