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

namespace ApiPlatform\Core\Bridge\Graphql\Resolver;

use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;

/**
 * Base resolver factory.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 *
 * @internal
 */
class AbstractResolverFactory
{
    protected $subresourceDataProvider;

    public function __construct(SubresourceDataProviderInterface $subresourceDataProvider)
    {
        $this->subresourceDataProvider = $subresourceDataProvider;
    }

    /**
     * @return object|null
     */
    protected function getSubresource(string $rootClass, array $rootResolvedFields, array $rootIdentifiers, string $rootProperty, string $subresourceClass, bool $isCollection)
    {
        $identifiers = [];
        foreach ($rootIdentifiers as $rootIdentifier) {
            if (isset($rootResolvedFields[$rootIdentifier])) {
                $identifiers[$rootIdentifier] = $rootResolvedFields[$rootIdentifier];
            }
        }

        return $this->subresourceDataProvider->getSubresource($subresourceClass, $identifiers, [
            'property' => $rootProperty,
            'identifiers' => array_map(function ($rootIdentifier) use ($rootClass) {return [$rootIdentifier, $rootClass]; }, $rootIdentifiers),
            'collection' => $isCollection,
        ]);
    }
}
