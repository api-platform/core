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

namespace ApiPlatform\Symfony\Validator;

use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;

trait ValidationGroupsExtractorTrait
{
    /**
     * A service locator for ValidationGroupsGenerator.
     */
    private ?ContainerInterface $container = null;

    public function getValidationGroups(\Closure|array|string|null $validationGroups, ?object $data = null): string|array|GroupSequence|null
    {
        if (null === $validationGroups) {
            return $validationGroups;
        }

        if (
            $this->container
            && \is_string($validationGroups)
            && $this->container->has($validationGroups)
            && ($service = $this->container->get($validationGroups))
            && \is_callable($service)
        ) {
            $validationGroups = $service($data);
        } elseif (\is_callable($validationGroups)) {
            $validationGroups = $validationGroups($data);
        }

        if (!$validationGroups instanceof GroupSequence) {
            $validationGroups = (array) $validationGroups;
        }

        return $validationGroups;
    }
}
