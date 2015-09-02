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

/**
 * Default API action deleting a resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class DeleteItemAction
{
    use ActionUtilTrait;

    /**
     * @var DataProviderInterface
     */
    private $dataProvider;

    public function __construct(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
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

        return $this->getItem($this->dataProvider, $resourceClass, $operationName, $id);
    }
}
