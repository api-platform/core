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

namespace ApiPlatform\Metadata;

use ApiPlatform\Metadata\GraphQl\Operation as GraphQlOperation;

trait WithResourceTrait
{
    public function withResource(ApiResource $resource): self
    {
        return $this->copyFrom($resource);
    }

    /**
     * @param ApiResource|Operation|GraphQlOperation $resource
     */
    private function copyFrom($resource): self
    {
        $self = clone $this;
        foreach (get_class_methods($resource) as $methodName) {
            if (0 !== strpos($methodName, 'get')) {
                continue;
            }

            if (!method_exists($self, $methodName)) {
                continue;
            }

            $operationValue = $self->{$methodName}();
            if (null !== $operationValue && [] !== $operationValue) {
                continue;
            }

            $self = $self->{'with'.substr($methodName, 3)}($resource->{$methodName}());
        }

        return $self;
    }
}
