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

namespace ApiPlatform\Core\Api;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\RuntimeException;

/**
 * Converts item and resources to IRI and vice versa.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @group legacy
 * @expectedDeprecation The ContextAwareIriConverterInterface interface is deprecated since version 2.7 and will be removed in 3.0. Provide an implementation of IriConverterInterface instead.
 */
interface ContextAwareIriConverterInterface extends IriConverterInterface
{
    /**
     * Gets the IRI associated with the given item.
     *
     * @param object $item
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function getIriFromItem($item, int $referenceType = UrlGeneratorInterface::ABS_PATH, array $context = []): string;

    /**
     * Gets the IRI associated with the given resource class.
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function getIriFromResourceClass(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH, array $context = []): string;
}
