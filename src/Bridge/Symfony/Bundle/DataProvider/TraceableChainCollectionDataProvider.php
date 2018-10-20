<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\DataProvider;

use ApiPlatform\Core\DataProvider\ChainCollectionDataProvider;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ContextAwareCollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
final class TraceableChainCollectionDataProvider implements ContextAwareCollectionDataProviderInterface
{
    private $dataProviders = [];
    private $context = [];
    private $providersResponse = [];

    public function __construct(CollectionDataProviderInterface $collectionDataProvider)
    {
        if ($collectionDataProvider instanceof ChainCollectionDataProvider) {
            $reflection = new \ReflectionProperty(ChainCollectionDataProvider::class, 'dataProviders');
            $reflection->setAccessible(true);
            $this->dataProviders = $reflection->getValue($collectionDataProvider);
        }
    }

    public function getProvidersResponse(): array
    {
        return $this->providersResponse;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getCollection(string $resourceClass, string $operationName = null, array $context = [])
    {
        $this->context = $context;
        foreach ($this->dataProviders as $dataProvider) {
            $this->providersResponse[\get_class($dataProvider)] = null;
        }

        foreach ($this->dataProviders as $dataProvider) {
            try {
                if ($dataProvider instanceof RestrictedDataProviderInterface
                    && !$dataProvider->supports($resourceClass, $operationName, $context)) {
                    $this->providersResponse[\get_class($dataProvider)] = false;
                    continue;
                }
                $this->providersResponse[\get_class($dataProvider)] = true;

                return $dataProvider->getCollection($resourceClass, $operationName, $context);
            } catch (ResourceClassNotSupportedException $e) {
                @trigger_error(sprintf('Throwing a "%s" in a data provider is deprecated in favor of implementing "%s"', ResourceClassNotSupportedException::class, RestrictedDataProviderInterface::class), E_USER_DEPRECATED);
                $this->providersResponse[\get_class($dataProvider)] = false;
                continue;
            }
        }

        return [];
    }
}
