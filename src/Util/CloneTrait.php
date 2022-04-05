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

namespace ApiPlatform\Util;

/**
 * Clones given data if cloneable.
 *
 * @internal
 *
 * @author Quentin Barloy <quentin.barloy@gmail.com>
 */
trait CloneTrait
{
    public function clone($data)
    {
        if (!\is_object($data)) {
            return $data;
        }

        try {
            return (new \ReflectionClass($data))->isCloneable() ? clone $data : null;
        } catch (\ReflectionException $reflectionException) {
            return null;
        }
    }
}

class_alias(CloneTrait::class, \ApiPlatform\Core\Util\CloneTrait::class);
