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

use ApiPlatform\Core\Bridge\Rector\Rules\ApiResourceAnnotationToApiResourceAttributeRector;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php80\ValueObject\AnnotationToAttribute;
use Rector\Renaming\Rector\Namespace_\RenameNamespaceRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\SymfonyPhpConfig\ValueObjectInliner;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_80);
    $parameters->set(Option::AUTO_IMPORT_NAMES, true);

    $services = $containerConfigurator->services();

    $services->set(RenameNamespaceRector::class)
        ->call('configure', [[
            RenameNamespaceRector::OLD_TO_NEW_NAMESPACES => [
                'ApiPlatform\Core\Annotation\ApiResource' => 'ApiPlatform\Metadata\ApiResource',
            ],
        ]]);

    $services->set(ApiResourceAnnotationToApiResourceAttributeRector::class)
        ->call('configure', [[
            ApiResourceAnnotationToApiResourceAttributeRector::ANNOTATION_TO_ATTRIBUTE => ValueObjectInliner::inline([
                new AnnotationToAttribute(
                    \ApiPlatform\Core\Annotation\ApiResource::class,
                    \ApiPlatform\Metadata\ApiResource::class
                ),
            ]),
        ]]);
};
