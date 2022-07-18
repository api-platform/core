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

namespace ApiPlatform\Api;

use Psr\Container\ContainerInterface;

/**
 * Manipulates filters with a backward compatibility between the new filter locator and the deprecated filter collection.
 *
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 *
 * @internal
 */
trait FilterLocatorTrait
{
    /** @var ContainerInterface */
    private $filterLocator;

    /**
     * Sets a filter locator with a backward compatibility.
     *
     * @param ContainerInterface|null $filterLocator
     */
    private function setFilterLocator($filterLocator, bool $allowNull = false): void
    {
        $this->filterLocator = $filterLocator;
    }

    /**
     * Gets a filter with a backward compatibility.
     */
    private function getFilter(string $filterId): ?FilterInterface
    {
        if ($this->filterLocator instanceof ContainerInterface && $this->filterLocator->has($filterId)) {
            return $this->filterLocator->get($filterId);
        }

        return null;
    }
}
