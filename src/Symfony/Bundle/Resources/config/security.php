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

    $services->alias('api_platform.security.expression_language', 'security.expression_language');

    $services->set('api_platform.security.resource_access_checker', 'ApiPlatform\Symfony\Security\ResourceAccessChecker')
        ->args([
            service('api_platform.security.expression_language')->nullOnInvalid(),
            service('security.authentication.trust_resolver')->nullOnInvalid(),
            service('security.role_hierarchy')->nullOnInvalid(),
            service('security.token_storage')->nullOnInvalid(),
            service('security.authorization_checker')->nullOnInvalid(),
        ]);

    $services->alias('ApiPlatform\Metadata\ResourceAccessCheckerInterface', 'api_platform.security.resource_access_checker');

    $services->set('api_platform.security.expression_language_provider', 'ApiPlatform\Symfony\Security\Core\Authorization\ExpressionLanguageProvider')
        ->tag('security.expression_language_provider');
};
