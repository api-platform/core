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

use Dunglas\ApiBundle\Api\ItemDataProviderInterface;
use Dunglas\ApiBundle\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default API action deleting a resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DeleteItemAction
{
    use ActionUtilTrait;

    private $itemDataProvider;

    public function __construct(ItemDataProviderInterface $itemDataProvider)
    {
        $this->itemDataProvider = $itemDataProvider;
    }

    /**
     * Returns an item to delete.
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
        list($resourceClass, , $operationName) = $this->extractAttributes($request);

        return $this->getItem($this->itemDataProvider, $resourceClass, $operationName, $id);
    }
}
