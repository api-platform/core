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

namespace ApiPlatform\Doctrine\Common;

use ApiPlatform\Doctrine\Common\Filter\LoggerAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\ManagerRegistryAwareInterface;
use ApiPlatform\Doctrine\Common\Filter\PropertyAwareFilterInterface;
use ApiPlatform\Metadata\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

trait ParameterExtensionTrait
{
    use ParameterValueExtractorTrait;

    protected ContainerInterface $filterLocator;
    protected ?ManagerRegistry $managerRegistry = null;
    protected ?LoggerInterface $logger = null;

    /**
     * @param object    $filter    the filter instance to configure
     * @param Parameter $parameter the operation parameter associated with the filter
     */
    private function configureFilter(object $filter, Parameter $parameter): void
    {
        if ($this->managerRegistry && $filter instanceof ManagerRegistryAwareInterface && !$filter->hasManagerRegistry()) {
            $filter->setManagerRegistry($this->managerRegistry);
        }

        if ($this->logger && $filter instanceof LoggerAwareInterface && !$filter->hasLogger()) {
            $filter->setLogger($this->logger);
        }

        if ($filter instanceof PropertyAwareFilterInterface) {
            $properties = [];
            // Check if the filter has getProperties method (e.g., if it's an AbstractFilter)
            if (method_exists($filter, 'getProperties')) { // @phpstan-ignore-line todo 5.x remove this check @see interface
                $properties = $filter->getProperties() ?? [];
            }

            $propertyKey = $parameter->getProperty() ?? $parameter->getKey();
            foreach ($parameter->getProperties() ?? [$propertyKey] as $property) {
                if (!isset($properties[$property])) {
                    $properties[$property] = $parameter->getFilterContext();
                }
            }

            $filter->setProperties($properties);
        }
    }
}
