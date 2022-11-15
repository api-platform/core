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

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Api\IriConverterInterface as LegacyIriConverterInterface;
use ApiPlatform\Metadata\Operation;

/**
 * This IRI converter calls the legacy IriConverter on legacy resources.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @internal
 */
final class LegacyIriConverter implements IriConverterInterface
{
    private $legacyIriConverter;
    private $iriConverter;

    public function __construct(LegacyIriConverterInterface $legacyIriConverter, IriConverterInterface $iriConverter)
    {
        $this->legacyIriConverter = $legacyIriConverter;
        $this->iriConverter = $iriConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function getResourceFromIri(string $iri, array $context = [], ?Operation $operation = null)
    {
        if (!$operation && !($operation = $context['operation'] ?? null)) {
            return $this->iriConverter->getResourceFromIri($iri, $context);
        }

        if (!($operation->getExtraProperties()['is_legacy_resource_metadata'] ?? false) && !($operation->getExtraProperties()['is_legacy_subresource'] ?? false)) {
            return $this->iriConverter->getResourceFromIri($iri, $context, $operation);
        }

        return $this->legacyIriConverter->getItemFromIri($iri, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getIriFromResource($item, int $referenceType = UrlGeneratorInterface::ABS_PATH, Operation $operation = null, array $context = []): ?string
    {
        return $this->iriConverter->getIriFromResource($item, $referenceType, $operation, $context);
    }
}
