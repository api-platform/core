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

/**
 * @internal since api-platform/metadata 4.2
 */
trait WithResourceTrait
{
    protected function copyFrom(Metadata $resource, array $ignoredOptions = []): static
    {
        $self = clone $this;
        foreach (get_class_methods($resource) as $method) {
            if (
                method_exists($self, $method)
                && preg_match('/^(?:get|is|can)(.*)/', (string) $method, $matches)
                && (!$ignoredOptions || !\in_array(lcfirst($matches[1]), $ignoredOptions, true))
                && null === $self->{$method}()
                && null !== $val = $resource->{$method}()
            ) {
                $self = $self->{"with{$matches[1]}"}($val);
            }
        }

        return $self->withExtraProperties(array_merge($resource->getExtraProperties(), $self->getExtraProperties()));
    }
}
