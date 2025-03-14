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

namespace ApiPlatform\Laravel\Eloquent\State;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;

/**
 * @internal
 */
trait ModelClassTrait
{
    /**
     * @return class-string
     */
    private function getModelClass(Operation $operation): string
    {
        $modelClass = $operation->getClass();

        if (($options = $operation->getStateOptions()) && $options instanceof Options && $options->getModelClass()) {
            $modelClass = $options->getModelClass();
        }

        if (!$modelClass || !class_exists($modelClass)) {
            throw new RuntimeException(\sprintf('No class found on operation %s.', $operation->getName()));
        }

        return $modelClass;
    }
}
