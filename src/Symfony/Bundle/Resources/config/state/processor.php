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

use ApiPlatform\State\Processor\AddLinkHeaderProcessor;
use ApiPlatform\State\Processor\RespondProcessor;
use ApiPlatform\State\Processor\SerializeProcessor;
use ApiPlatform\State\Processor\WriteProcessor;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->alias('api_platform.state_processor.main', 'api_platform.state_processor.respond');

    $services->set('api_platform.state_processor.serialize', SerializeProcessor::class)
        ->decorate('api_platform.state_processor.main', null, 200)
        ->args([
            service('api_platform.state_processor.serialize.inner'),
            service('api_platform.serializer'),
            service('api_platform.serializer.context_builder'),
        ]);

    $services->set('api_platform.state_processor.write', WriteProcessor::class)
        ->decorate('api_platform.state_processor.main', null, 100)
        ->args([
            service('api_platform.state_processor.write.inner'),
            service('api_platform.state_processor.locator'),
        ]);

    $services->set('api_platform.state_processor.respond', RespondProcessor::class)
        ->args([
            service('api_platform.iri_converter'),
            service('api_platform.resource_class_resolver'),
            service('api_platform.metadata.operation.metadata_factory'),
        ]);

    $services->set('api_platform.state_processor.add_link_header', AddLinkHeaderProcessor::class)
        ->decorate('api_platform.state_processor.respond', null, 0)
        ->args([service('api_platform.state_processor.add_link_header.inner')]);
};
