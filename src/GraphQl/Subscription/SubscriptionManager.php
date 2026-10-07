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

        $previousObject = $context['graphql_context']['previous_object'] ?? null;
        $privateFieldData = $this->getPrivateFieldData($operation, $previousObject);
        $collection = $operation instanceof CollectionOperationInterface;
        $cacheKey = $this->getSubscriptionKey($iri, $operation, $privateFieldData);
        unset($result['clientSubscriptionId']);

        $identity = $fields + ($collection ? ['__collection' => true] : []) + ['__subscription_scope' => $cacheKey];

        return $this->store->register(
            $cacheKey,
            $fields,
            $result,
            $collection,
            fn (): string => $this->subscriptionIdentifierGenerator->generateSubscriptionIdentifier($identity)
        );
    }

    /** @return iterable<SubscriptionUpdate> */
    public function getUpdates(object $object, Subscription $operation, string $type = 'update'): iterable
    {
        if ((!$operation instanceof CollectionOperationInterface && 'create' === $type) || false === $operation->getMercure()) {
            return;
        }
        if ('delete' === $type) {
            yield from $this->getDeleteUpdates($object, $operation);

            return;
        }

        $key = $this->getSubscriptionKey($this->iriConverter->getIriFromResource($object), $operation, $this->getPrivateFieldData($operation, $object));
        foreach ($this->store->getSubscriptions($key, $operation instanceof CollectionOperationInterface) as $subscription) {
            $data = $this->normalizeProcessor->process($object, $operation, [], ['fields' => $subscription->fields]);
            unset($data['clientSubscriptionId']);
            $update = $this->store->prepareUpdate($subscription, $data);
            if (null !== $update) {
                yield $update;
            }
        }
    }

    public function acknowledge(SubscriptionUpdate $update): void
    {
        $this->store->acknowledge($update);
    }

    private function getSubscriptionKey(string $iri, Subscription $operation, array $privateFieldData): string
    {
        $collection = $operation instanceof CollectionOperationInterface;

        $key = 'graphql_subscription_'.hash('sha256', serialize([
            'resource' => $operation->getClass(),
            'operation' => $operation->getName(),
            'collection' => $collection,
            'iri' => $collection ? null : $iri,
        ]));

        return [] === $privateFieldData ? $key : $key.'_'.hash('sha256', serialize($privateFieldData));
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

    /** @return iterable<SubscriptionUpdate> */
    private function getDeleteUpdates(object $object, Subscription $operation): iterable
    {
        $privateFieldData = [];
        foreach ($this->getPrivateFields($operation) as $privateField) {
            if (!\array_key_exists($privateField, $object->private)) {
                throw new RuntimeException(\sprintf('Private field "%s" is missing from the delete snapshot for subscription "%s".', $privateField, $operation->getName()));
            }
            $privateFieldData[$privateField] = $object->private[$privateField];
        }
        $key = $this->getSubscriptionKey($object->id, $operation, $privateFieldData);
        $payload = ['type' => 'delete', 'payload' => ['id' => $object->id, 'iri' => $object->iri, 'type' => $object->type]];

        return $this->store->getDeleteUpdates($key, $operation instanceof CollectionOperationInterface, $payload);
    }
}
