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
    ApiPlatform\Exception\FilterValidationException::class => ApiPlatform\ParameterValidator\Exception\ValidationException::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\ArrayItems::class => ApiPlatform\ParameterValidator\Validator\ArrayItems::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\Bounds::class => ApiPlatform\ParameterValidator\Validator\Bounds::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\Enum::class => ApiPlatform\ParameterValidator\Validator\Enum::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\Length::class => ApiPlatform\ParameterValidator\Validator\Length::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\MultipleOf::class => ApiPlatform\ParameterValidator\Validator\MultipleOf::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\Pattern::class => ApiPlatform\ParameterValidator\Validator\Pattern::class,
    ApiPlatform\Api\QueryParameterValidator\Validator\Required::class => ApiPlatform\ParameterValidator\Validator\Required::class,
];

spl_autoload_register(function ($className) use ($deprecatedClassesWithAliases): void {
    if (isset($deprecatedClassesWithAliases[$className])) {
        trigger_deprecation('api-platform/core', '4.0', sprintf('The class %s is deprecated, use %s instead.', $className, $deprecatedClassesWithAliases[$className]));

        class_alias($deprecatedClassesWithAliases[$className], $className);

        return;
    }
});
