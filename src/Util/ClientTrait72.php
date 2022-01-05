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

namespace ApiPlatform\Core\Util;

trait ClientTrait72
{
    public function withOptions(array $options): self
    {
        $clone = clone $this;
        $clone->defaultOptions = self::mergeDefaultOptions($options, $this->defaultOptions);

        return $clone;
    }
}
