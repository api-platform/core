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

namespace ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\ErrorResource;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

/**
 * Normalizes enabled formats.
 *
 * Formats hierarchy:
 * * resource formats
 *   * resource input/output formats
 *     * operation formats
 *       * operation input/output formats
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class FormatsResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $decorated, private readonly array $formats, private readonly array $patchFormats, private readonly ?array $errorFormats = null)
    {
    }

    /**
     * Adds the formats attributes.
     *
     * @see UriTemplateResourceMetadataCollectionFactory
     *
     * @throws ResourceClassNotFoundException
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $index => $resourceMetadata) {
            $rawResourceFormats = $resourceMetadata->getFormats();
            $resourceFormats = null === $rawResourceFormats ? $this->formats : $this->normalizeFormats($rawResourceFormats);
            $resourceInputFormats = $resourceMetadata->getInputFormats() ? $this->normalizeFormats($resourceMetadata->getInputFormats()) : $resourceFormats;
            $resourceOutputFormats = $resourceMetadata->getOutputFormats() ? $this->normalizeFormats($resourceMetadata->getOutputFormats()) : $resourceFormats;
            if ($resourceMetadata instanceof ErrorResource) {
                $resourceOutputFormats = $resourceMetadata->getOutputFormats() ? $this->normalizeFormats($resourceMetadata->getOutputFormats()) : ($this->errorFormats ?? []);
            }

            $resourceMetadataCollection[$index] = $resourceMetadataCollection[$index]->withOperations($this->normalize($resourceInputFormats, $resourceOutputFormats, $resourceMetadata->getOperations()));
        }

        return $resourceMetadataCollection;
    }

    private function normalize(array $resourceInputFormats, array $resourceOutputFormats, Operations $operations): Operations
    {
        $newOperations = [];
        $patchFormats = null;
        foreach ($operations as $operationName => $operation) {
            if ($operation->getFormats()) {
                $operation = $operation->withFormats($this->normalizeFormats($operation->getFormats()));
            }

            if (($isPatch = 'PATCH' === $operation->getMethod()) && !$operation->getFormats() && !$operation->getInputFormats()) {
                $operation = $operation->withInputFormats($this->patchFormats);
            }

            $operation = $operation->withInputFormats($operation->getInputFormats() ? $this->normalizeFormats($operation->getInputFormats()) : $operation->getFormats() ?? $resourceInputFormats);
            $operation = $operation->withOutputFormats($operation->getOutputFormats() ? $this->normalizeFormats($operation->getOutputFormats()) : $operation->getFormats() ?? $resourceOutputFormats);

            if ($isPatch) {
                $patchFormats = $operation->getInputFormats();
            }

            $newOperations[$operationName] = $operation;
        }

        if (!$patchFormats) {
            return new Operations($newOperations);
        }

        // Prepare an Accept-Patch header
        foreach ($newOperations as $operationName => $operation) {
            if ($operation instanceof CollectionOperationInterface) {
                continue;
            }

            $patchMimeTypes = [];

            foreach ($patchFormats as $mimeTypes) {
                foreach ($mimeTypes as $mimeType) {
                    $patchMimeTypes[] = $mimeType;
                }
            }

            $newOperations[$operationName] = $operation->withAcceptPatch(implode(', ', $patchMimeTypes));
        }

        return new Operations($newOperations);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function normalizeFormats(array|string $currentFormats): array
    {
        $currentFormats = (array) $currentFormats;

        $normalizedFormats = [];
        foreach ($currentFormats as $format => $value) {
            if (!is_numeric($format)) {
                $normalizedFormats[$format] = (array) $value;
                continue;
            }
            if (!\is_string($value)) {
                throw new InvalidArgumentException(sprintf("The 'formats' attributes value must be a string when trying to include an already configured format, %s given.", \gettype($value)));
            }
            if (\array_key_exists($value, $this->formats)) {
                $normalizedFormats[$value] = $this->formats[$value];
                continue;
            }

            throw new InvalidArgumentException(sprintf("You either need to add the format '%s' to your project configuration or declare a mime type for it in your annotation.", $value));
        }

        return $normalizedFormats;
    }
}
