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

namespace ApiPlatform\State\Util;

use ApiPlatform\Doctrine\Odm\State\Options as ODMOptions;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Laravel\Eloquent\State\Options as EloquentOptions;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\OptionsInterface;

/**
 * @internal
 */
trait StateOptionsTrait
{
    /**
     * @param Operation   $operation    the operation
     * @param string|null $defaultClass the default class to return if no state options class is found
     * @param string      $optionsType  an option type to test against (defaults to ApiPlatform\State\OptionsInterface)
     *
     * @return class-string|null
     */
    public function getStateOptionsClass(Operation $operation, ?string $defaultClass = null, string $optionsType = OptionsInterface::class): ?string
    {
        if (!$options = $operation->getStateOptions()) {
            return $defaultClass;
        }

        if (!$options instanceof $optionsType) {
            return $defaultClass;
        }

        if (class_exists(Options::class) && $options instanceof Options && ($e = $options->getEntityClass())) {
            return $e;
        }

        if (class_exists(ODMOptions::class) && $options instanceof ODMOptions && ($e = $options->getDocumentClass())) {
            return $e;
        }

        if (class_exists(EloquentOptions::class) && $options instanceof EloquentOptions && ($e = $options->getModelClass())) {
            return $e;
        }

        return $defaultClass;
    }
}
