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

namespace ApiPlatform\Core\DataProvider;

use ApiPlatform\Core\Exception\ResourceClassNotSupportedException;

/**
 * Tries each configured data provider and returns the result of the first able to handle the resource class.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class ChainItemDataProvider implements ItemDataProviderInterface
{
    private $dataProviders;

    /**
     * @param ItemDataProviderInterface[] $dataProviders
     */
    public function __construct(array $dataProviders)
    {
        $this->dataProviders = $dataProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = [])
    {
        foreach ($this->dataProviders as $dataProvider) {
            try {
                if ($dataProvider instanceof RestrictedDataProviderInterface
                    && !$dataProvider->supports($resourceClass, $operationName, $context)) {
                    continue;
                }

                return $dataProvider->getItem($resourceClass, $id, $operationName, $context);
            } catch (ResourceClassNotSupportedException $e) {
                @trigger_error(sprintf('Throwing a "%s" is deprecated in favor of implementing "%s"', get_class($e), RestrictedDataProviderInterface::class), E_USER_DEPRECATED);
                continue;
            }
        }

        return null;
    }
}
