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

$deprecatedClassesWithAliases = [
    ApiPlatform\HttpCache\EventListener\AddHeadersListener::class => ApiPlatform\Symfony\EventListener\AddHeadersListener::class,
    ApiPlatform\HttpCache\EventListener\AddTagsListener::class => ApiPlatform\Symfony\EventListener\AddTagsListener::class,
];

spl_autoload_register(function ($className) use ($deprecatedClassesWithAliases): void {
    if (isset($deprecatedClassesWithAliases[$className])) {
        trigger_deprecation('api-platform/core', '4.0', sprintf('The class %s is deprecated, use %s instead.', $className, $deprecatedClassesWithAliases[$className]));

        class_alias($deprecatedClassesWithAliases[$className], $className);

        return;
    }
});
