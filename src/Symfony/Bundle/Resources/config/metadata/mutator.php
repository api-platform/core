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

use ApiPlatform\Metadata\Mutator\OperationMutatorCollection;
use ApiPlatform\Metadata\Mutator\ResourceMutatorCollection;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('api_platform.metadata.mutator_collection.resource', ResourceMutatorCollection::class);

    $services->set('api_platform.metadata.mutator_collection.operation', OperationMutatorCollection::class);
};
