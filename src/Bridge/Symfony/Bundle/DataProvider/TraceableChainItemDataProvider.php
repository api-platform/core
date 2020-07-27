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

use ApiPlatform\Core\DataProvider\ChainItemDataProvider;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;

/**
 * @author Anthony GRASSIOT <antograssiot@free.fr>
 */
final class TraceableChainItemDataProvider implements ItemDataProviderInterface
{
    private $dataProviders = [];
    private $context = [];
    private $providersResponse = [];

    public function __construct(ItemDataProviderInterface $itemDataProvider)
    {
        if ($itemDataProvider instanceof ChainItemDataProvider) {
            $this->dataProviders = $itemDataProvider->dataProviders;
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

    public function getItem(string $resourceClass, array $identifiers, string $operationName = null, array $context = [])
    {
        $this->context = $context;
        $match = false;
        $result = null;

        foreach ($this->dataProviders as $dataProvider) {
            $this->providersResponse[\get_class($dataProvider)] = $match ? null : false;
            if ($match) {
                continue;
            }
            try {
                if ($dataProvider instanceof RestrictedDataProviderInterface
                    && !$dataProvider->supports($resourceClass, $operationName, $context)) {
                    continue;
                }

                $result = $dataProvider->getItem($resourceClass, $identifiers, $operationName, $context);
                $this->providersResponse[\get_class($dataProvider)] = $match = true;
            } catch (ResourceClassNotSupportedException $e) {
                @trigger_error(sprintf('Throwing a "%s" is deprecated in favor of implementing "%s"', \get_class($e), RestrictedDataProviderInterface::class), E_USER_DEPRECATED);
                continue;
            }
        }

        return $result;
    }
}
