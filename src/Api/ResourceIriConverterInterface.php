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
 * @author Maxime Veber <maxime.veber@nekland.fr>
 */
interface ResourceIriConverterInterface extends IriConverterInterface
{
    /**
     * Gets the IRI associated with the given item and resource class.
     *
     * @param object $item
     * @param string $resourceClass resource class to use for generation
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @return string Class name of resource
     */
    public function getIriFromItemWithResource($item, string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): string;
}
