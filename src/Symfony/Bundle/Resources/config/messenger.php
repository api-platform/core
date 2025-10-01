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

return function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->alias('api_platform.message_bus', 'messenger.default_bus');

    $services->set('api_platform.messenger.metadata.resource.metadata_collection_factory', 'ApiPlatform\Symfony\Messenger\Metadata\MessengerResourceMetadataCollectionFactory')
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, 50)
        ->args([service('api_platform.messenger.metadata.resource.metadata_collection_factory.inner')]);

    $services->set('ApiPlatform\Symfony\Messenger\Processor', 'ApiPlatform\Symfony\Messenger\Processor')
        ->args([service('api_platform.message_bus')])
        ->tag('api_platform.state_processor', ['priority' => -900]);
};
