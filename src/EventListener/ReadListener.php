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

namespace ApiPlatform\Core\EventListener;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\PropertyNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use ApiPlatform\Core\Util\RequestParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Retrieves data from the applicable data provider and sets it as a request parameter called data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ReadListener
{
    private $collectionDataProvider;
    private $itemDataProvider;
    private $subresourceDataProvider;
    private $serializerContextBuilder;

    public function __construct(CollectionDataProviderInterface $collectionDataProvider, ItemDataProviderInterface $itemDataProvider, SubresourceDataProviderInterface $subresourceDataProvider = null, SerializerContextBuilderInterface $serializerContextBuilder = null)
    {
        $this->collectionDataProvider = $collectionDataProvider;
        $this->itemDataProvider = $itemDataProvider;
        $this->subresourceDataProvider = $subresourceDataProvider;
        $this->serializerContextBuilder = $serializerContextBuilder;
    }

    /**
     * Calls the data provider and sets the data attribute.
     *
     * @param GetResponseEvent $event
     *
     * @throws NotFoundHttpException
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (
            !($attributes = RequestAttributesExtractor::extractAttributes($request))
            || !$attributes['receive']
        ) {
            return;
        }

        if (null === $filters = $request->attributes->get('_api_filters')) {
            $queryString = RequestParser::getQueryString($request);
            $filters = $queryString ? RequestParser::parseRequestParams($queryString) : null;
        }

        $context = null === $filters ? [] : ['filters' => $filters];
        if ($this->serializerContextBuilder) {
            // Builtin data providers are able to use the serialization context to automatically add join clauses
            $context += $normalizationContext = $this->serializerContextBuilder->createFromRequest($request, true, $attributes);
            $request->attributes->set('_api_normalization_context', $normalizationContext);
        }

        $data = [];
        if (isset($attributes['item_operation_name'])) {
            $data = $this->getItemData($request, $attributes, $context);
        } elseif (isset($attributes['collection_operation_name'])) {
            $data = $this->getCollectionData($request, $attributes, $context);
        } elseif (isset($attributes['subresource_operation_name'])) {
            $data = $this->getSubresourceData($request, $attributes, $context);
        }

        $request->attributes->set('data', $data);
    }

    /**
     * Retrieves data for a collection operation.
     *
     * @return array|\Traversable|null
     */
    private function getCollectionData(Request $request, array $attributes, array $context)
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            return null;
        }

        return $this->collectionDataProvider->getCollection($attributes['resource_class'], $attributes['collection_operation_name'], $context);
    }

    /**
     * Gets data for an item operation.
     *
     * @throws NotFoundHttpException
     *
     * @return object|null
     */
    private function getItemData(Request $request, array $attributes, array $context)
    {
        $id = $request->attributes->get('id');
        try {
            $data = $this->itemDataProvider->getItem($attributes['resource_class'], $id, $attributes['item_operation_name'], $context);
        } catch (PropertyNotFoundException $e) {
            $data = null;
        }

        if (null === $data) {
            throw new NotFoundHttpException('Not Found');
        }

        return $data;
    }

    /**
     * Gets data for a nested operation.
     *
     * @throws NotFoundHttpException
     * @throws RuntimeException
     *
     * @return object|null
     */
    private function getSubresourceData(Request $request, array $attributes, array $context)
    {
        if (null === $this->subresourceDataProvider) {
            throw new RuntimeException('No subresource data provider.');
        }

        $attributes['subresource_context'] += $context;
        $identifiers = [];
        foreach ($attributes['subresource_context']['identifiers'] as $key => list($id, , $hasIdentifier)) {
            if (true === $hasIdentifier) {
                $identifiers[$id] = $request->attributes->get($id);
            }
        }

        $data = $this->subresourceDataProvider->getSubresource($attributes['resource_class'], $identifiers, $attributes['subresource_context'], $attributes['subresource_operation_name']);

        return $data;
    }
}
