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

namespace ApiPlatform\Core\OpenApi\Model;

trait ExtensionTrait
{
    public function __call(string $name, array $arguments)
    {
        if (0 !== strpos($name, 'withX')) {
            throw new \BadMethodCallException('Specification extensions must start with x!');
        }

        $clone = clone $this;
        $clone->{str_replace('withX', 'x-', $name)} = $arguments[0];

        return $clone;
    }
}
