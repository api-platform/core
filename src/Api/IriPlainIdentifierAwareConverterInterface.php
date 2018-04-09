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
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;

/**
 * Converts a plain identifier to an IRI
 *
 * @author Hamza Amrouche <hamza.simperfit@gmail.com>
 */
interface IriPlainIdentifierAwareConverterInterface extends IriConverterInterface
{
    /**
     * Gets the IRI associated with the given plain identifier.
     *
     * @param string|int|array $id
     * @param string           $resourceClass
     * @param int              $referenceType
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    public function getIriFromPlainIdentifier($id, string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): string;
}
