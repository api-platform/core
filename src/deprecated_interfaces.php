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

return [
    ApiPlatform\Core\DataTransformer\DataTransformerInitializerInterface::class => ApiPlatform\DataTransformer\DataTransformerInitializerInterface::class,
    ApiPlatform\Core\DataTransformer\DataTransformerInterface::class => ApiPlatform\DataTransformer\DataTransformerInterface::class,
    ApiPlatform\Core\Validator\ValidatorInterface::class => ApiPlatform\Symfony\EventListener\ValidatorInterface::class,
];
