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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ApiPlatform\Core\DataProvider\ChainCollectionDataProvider;
use ApiPlatform\Core\DataProvider\ChainItemDataProvider;
use ApiPlatform\Core\DataProvider\ChainSubresourceDataProvider;
use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\Pagination;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('api_platform.serializer_locator', ServiceLocator::class)
            ->args([['serializer' => service('api_platform.serializer')]])
            ->tag('container.service_locator')
        ->set('api_platform.item_data_provider', ChainItemDataProvider::class)
            ->args([tagged_iterator('api_platform.item_data_provider')])
        ->alias(ItemDataProviderInterface::class, 'api_platform.item_data_provider')
        ->set('api_platform.collection_data_provider', ChainCollectionDataProvider::class)
            ->args([tagged_iterator('api_platform.collection_data_provider')])
        ->alias(CollectionDataProviderInterface::class, 'api_platform.collection_data_provider')
        ->set('api_platform.subresource_data_provider', ChainSubresourceDataProvider::class)
            ->args([tagged_iterator('api_platform.subresource_data_provider')])
        ->alias(SubresourceDataProviderInterface::class, 'api_platform.subresource_data_provider')
        ->set('api_platform.pagination', Pagination::class)
            ->args([service('api_platform.metadata.resource.metadata_factory'), param('api_platform.collection.pagination'), param('api_platform.graphql.collection.pagination')])
        ->alias(Pagination::class, 'api_platform.pagination');
};
