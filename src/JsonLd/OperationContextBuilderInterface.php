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

namespace ApiPlatform\JsonLd;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\UrlGeneratorInterface;

/**
 * JSON-LD context builder that is aware of the current operation.
 *
 * This interface extends ContextBuilderInterface with operation-aware methods
 * to correctly resolve context URIs when a resource class has multiple
 * ApiResource attributes with different shortNames.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
interface OperationContextBuilderInterface extends ContextBuilderInterface
{
    /**
     * Gets the URI of the resource context for a specific operation.
     */
    public function getResourceContextUriFromOperation(HttpOperation $operation, ?int $referenceType = null): string;

    /**
     * Gets the resource context for a specific operation.
     */
    public function getResourceContextFromOperation(HttpOperation $operation, string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): array;
}
