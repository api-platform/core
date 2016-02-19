<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Action;

use ApiPlatform\Builder\Api\ItemDataProviderInterface;
use ApiPlatform\Builder\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Updates a resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PutItemAction
{
    use ActionUtilTrait;

    private $itemDataProvider;
    private $serializer;

    public function __construct(ItemDataProviderInterface $itemDataProvider, SerializerInterface $serializer)
    {
        $this->itemDataProvider = $itemDataProvider;
        $this->serializer = $serializer;
    }

    /**
     * Create a new item.
     *
     * @param Request    $request
     * @param string|int $id
     *
     * @return mixed
     *
     * @throws NotFoundHttpException
     * @throws RuntimeException
     */
    public function __invoke(Request $request, $id)
    {
        list($resourceClass,, $operationName, $format) = $this->extractAttributes($request);
        $data = $this->getItem($this->itemDataProvider, $resourceClass, $operationName, $id);

        $context = ['object_to_populate' => $data, 'resource_class' => $resourceClass, 'item_operation_name' => $operationName];

        return $this->serializer->deserialize($request->getContent(), $resourceClass, $format, $context);
    }
}
