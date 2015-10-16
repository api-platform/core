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

use Dunglas\ApiBundle\Event\DataEvent;
use Dunglas\ApiBundle\Event\Events;
use Dunglas\ApiBundle\Exception\RuntimeException;
use Dunglas\ApiBundle\Model\DataProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default API action retrieving a collection of resources.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class GetCollectionAction
{
    use ActionUtilTrait;

    /**
     * @var DataProviderInterface
     */
    private $dataProvider;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(DataProviderInterface $dataProvider, EventDispatcherInterface $eventDispatcher)
    {
        $this->dataProvider = $dataProvider;
        $this->eventDispatcher = $eventDispatcher;
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
        list($resourceType) = $this->extractAttributes($request);

        $data = $this->dataProvider->getCollection($resourceType, $request);

        $this->eventDispatcher->dispatch(Events::RETRIEVE_LIST, new DataEvent($resourceType, $data));

        return $data;
    }
}
