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
use ApiPlatform\Core\Http\RequestAttributesExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Add a new resource to a collection.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class PostCollectionAction
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var RequestAttributesExtractorInterface
     */
    private $attributesExtractor;

    public function __construct(SerializerInterface $serializer, RequestAttributesExtractorInterface $attributesExtractor)
    {
        $this->serializer = $serializer;
        $this->attributesExtractor = $attributesExtractor;
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
        $attributesBag = $this->attributesExtractor->extract($request);
        $context = [
            'resource_class' => $attributesBag->getResourceClass(),
            'collection_operation_name' => $attributesBag->getCollectionOperationName()
        ];

        return $this->serializer->deserialize(
            $request->getContent(),
            $attributesBag->getResourceClass(),
            $attributesBag->getFormat(),
            $context
        );
    }
}
