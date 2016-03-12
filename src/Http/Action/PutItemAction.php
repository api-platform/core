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

use ApiPlatform\Core\Api\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Http\ItemDataProvider;
use ApiPlatform\Core\Http\RequestAttributesExtractorInterface;
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
    /**
     * @var ItemDataProvider
     */
    private $itemDataProvider;
    private $serializer;
    private $attributesExtractor;

    public function __construct(ItemDataProviderInterface $itemDataProvider, SerializerInterface $serializer, RequestAttributesExtractorInterface $attributesExtractor)
    {
        $this->itemDataProvider = new ItemDataProvider($itemDataProvider);
        $this->serializer = $serializer;
        $this->attributesExtractor = $attributesExtractor;
    }

    /**
     * Create a new item.
     *
     * @param Request    $request
     * @param string|int $id
     *
     * @throws NotFoundHttpException
     * @throws RuntimeException
     *
     * @return mixed
     */
    public function __invoke(Request $request, $id)
    {
        $attributesBag = $this->attributesExtractor->extract($request);
        $data = $this->itemDataProvider->getItem(
            $this->itemDataProvider,
            $attributesBag->getResourceClass(),
            $attributesBag->getItemOperationName(),
            $id
        );
        $context = [
            'object_to_populate' => $data,
            'resource_class' => $attributesBag->getResourceClass(),
            'item_operation_name' => $attributesBag->getItemOperationName()
        ];

        return $this->serializer->deserialize(
            $request->getContent(),
            $attributesBag->getResourceClass(),
            $attributesBag->getFormat(),
            $context
        );
    }
}
