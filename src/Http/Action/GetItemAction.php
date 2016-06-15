<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Http\Action;

use ApiPlatform\Core\Api\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Http\ItemDataProvider;
use ApiPlatform\Core\Http\RequestAttributesExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default API action retrieving a resource (used for GET and DELETE methods).
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class GetItemAction
{
    /**
     * @var ItemDataProvider
     */
    private $itemDataProvider;

    /**
     * @var RequestAttributesExtractorInterface
     */
    private $attributesExtractor;

    public function __construct(ItemDataProviderInterface $itemDataProvider, RequestAttributesExtractorInterface $attributesExtractor)
    {
        $this->itemDataProvider = new ItemDataProvider($itemDataProvider);
        $this->attributesExtractor = $attributesExtractor;
    }

    /**
     * Retrieves an item.
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

        return $this->itemDataProvider->getItem(
            $this->itemDataProvider,
            $attributesBag->getResourceClass(),
            $attributesBag->getItemOperationName(),
            $id
        );
    }
}
