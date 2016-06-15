<?php

/*
 *  This file is part of the API Platform project.
 *
 *  (c) Kévin Dunglas <dunglas@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Http;

use ApiPlatform\Core\Api\ItemDataProviderInterface;
use ApiPlatform\Core\Exception\ExceptionInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\NotFoundHttpException;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
final class ItemDataProvider implements ItemDataProviderInterface
{
    /**
     * @var ItemDataProviderInterface
     */
    private $decoratedProvider;

    public function __construct(ItemDataProviderInterface $decoratedProvider)
    {
        $this->decoratedProvider = $decoratedProvider;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ResourceClassNotSupportedException
     * @throws NotFoundHttpException
     * @throws ExceptionInterface
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, bool $fetchData = false)
    {
        $data = $this->decoratedProvider->getItem($resourceClass, $id, $operationName, true);
        if (null !== $data) {
            return $data;
        }

        throw new NotFoundHttpException('Not Found');
    }
}
