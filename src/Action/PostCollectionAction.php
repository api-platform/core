<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Action;

use ApiPlatform\Core\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Add a new resource to a collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PostCollectionAction
{
    use ActionUtilTrait;

    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Hydrate an item to persist.
     *
     * @param Request $request
     *
     * @throws RuntimeException
     *
     * @return mixed
     */
    public function __invoke(Request $request)
    {
        list($resourceClass, $operationName, , $format) = $this->extractAttributes($request);
        $context = ['resource_class' => $resourceClass, 'collection_operation_name' => $operationName];

        return $this->serializer->deserialize($request->getContent(), $resourceClass, $format, $context);
    }
}
