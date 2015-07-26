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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default API action deleting a resource.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DeleteItemAction
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
     * Deletes an item.
     *
     * @param Request    $request
     * @param string|int $id
     *
     * @throws NotFoundHttpException
     * @throws RuntimeException
     */
    public function __invoke(Request $request, $id)
    {
        list($resourceType) = $this->extractAttributes($request);
        $data = $this->getItem($this->dataProvider, $resourceType, $id);

        $this->eventDispatcher->dispatch(Events::PRE_DELETE, new DataEvent($resourceType, $data));

        return;
    }
}
