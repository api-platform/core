<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Action;

use Dunglas\ApiBundle\Exception\RuntimeException;
use Dunglas\ApiBundle\Api\DataProviderInterface;
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

    /**
     * @var DataProviderInterface
     */
    private $dataProvider;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(DataProviderInterface $dataProvider, SerializerInterface $serializer)
    {
        $this->dataProvider = $dataProvider;
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
        list($resourceClass, , $operationName, $format) = $this->extractAttributes($request);
        $data = $this->getItem($this->dataProvider, $resourceClass, $operationName, $id);

        $context = ['object_to_populate' => $data, 'resource_class' => $resourceClass, 'item_operation_name' => $operationName];

        return $this->serializer->deserialize($request->getContent(), $resourceClass, $format, $context);
    }
}
