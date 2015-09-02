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

/**
 * Default API action retrieving a collection of resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class GetCollectionAction
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
     * Retrieves a collection of resources.
     *
     * @param Request $request
     *
     * @return array|\Dunglas\ApiBundle\Model\PaginatorInterface|\Traversable
     *
     * @throws RuntimeException
     */
    public function __invoke(Request $request)
    {
        list($resourceClass, $operationName) = $this->extractAttributes($request);

        return $this->dataProvider->getCollection($resourceClass, $operationName);
    }
}
