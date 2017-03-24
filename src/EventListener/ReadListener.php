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
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
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

    public function __construct(CollectionDataProviderInterface $collectionDataProvider, ItemDataProviderInterface $itemDataProvider)
    {
        $this->collectionDataProvider = $collectionDataProvider;
        $this->itemDataProvider = $itemDataProvider;
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
        try {
            $attributes = RequestAttributesExtractor::extractAttributes($request);
        } catch (RuntimeException $e) {
            return;
        }

        if (isset($attributes['collection_operation_name'])) {
            $data = $this->getCollectionData($request, $attributes);
        } else {
            $data = $this->getItemData($request, $attributes);
        }

        $request->attributes->set('data', $data);
    }

    /**
     * Retrieves data for a collection operation.
     *
     * @param Request $request
     * @param array   $attributes
     *
     * @return array|\Traversable|PaginatorInterface|null
     */
    private function getCollectionData(Request $request, array $attributes)
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            return;
        }

        return $this->collectionDataProvider->getCollection($attributes['resource_class'], $attributes['collection_operation_name']);
    }

    /**
     * Gets data for an item operation.
     *
     * @param Request $request
     * @param array   $attributes
     *
     * @throws NotFoundHttpException
     *
     * @return object|null
     */
    private function getItemData(Request $request, array $attributes)
    {
        $id = $request->attributes->get('id');
        $data = $this->itemDataProvider->getItem($attributes['resource_class'], $id, $attributes['item_operation_name']);

        if (null === $data) {
            throw new NotFoundHttpException('Not Found');
        }

        return $data;
    }
}
