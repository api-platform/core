<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\Model;

use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A chain of data providers.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class DataProviderChain implements DataProviderInterface
{
    /**
     * @var DataProviderInterface[]
     */
    private $dataProviders;

    /**
     * @param DataProviderInterface[] $dataProviders
     */
    public function __construct(array $dataProviders)
    {
        $this->dataProviders = $dataProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(ResourceInterface $resource, $id, $fetchData = false)
    {
        foreach ($this->dataProviders as $dataProvider) {
            if ($dataProvider->supports($resource) && $result = $dataProvider->getItem($resource, $id, $fetchData)) {
                return $result;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(ResourceInterface $resource, Request $request)
    {
        foreach ($this->dataProviders as $dataProvider) {
            if ($dataProvider->supports($resource) && $result = $dataProvider->getCollection($resource, $request)) {
                return $result;
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ResourceInterface $resource)
    {
        return true;
    }
}
