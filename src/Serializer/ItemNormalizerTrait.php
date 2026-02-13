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

namespace ApiPlatform\Serializer;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

/**
 * Shared functionality for generic item normalization and denormalization.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
trait ItemNormalizerTrait
{
    /**
     * {@inheritdoc}
     *
     * @throws NotNormalizableValueException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // Avoid issues with proxies if we populated the object
        if (isset($data['id']) && !isset($context[AbstractItemNormalizer::OBJECT_TO_POPULATE])) {
            if (isset($context['api_allow_update']) && true !== $context['api_allow_update']) {
                throw new NotNormalizableValueException('Update is not allowed for this operation.');
            }

            if (isset($context['resource_class'])) {
                if ($this->updateObjectToPopulate($data, $context)) {
                    unset($data['id']);
                }
            } else {
                // See https://github.com/api-platform/core/pull/2326 to understand this message.
                $this->logger->warning('The "resource_class" key is missing from the context.', [
                    'context' => $context,
                ]);
            }
        }

        return parent::denormalize($data, $type, $format, $context);
    }

    private function updateObjectToPopulate(array $data, array &$context): bool
    {
        try {
            $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] = $this->iriConverter->getResourceFromIri((string) $data['id'], $context + ['fetch_data' => true]);

            return true;
        } catch (InvalidArgumentException) {
            $operation = $this->resourceMetadataCollectionFactory?->create($context['resource_class'])->getOperation();
            if (
                !$operation || (
                    null !== ($context['uri_variables'] ?? null)
                    && $operation instanceof HttpOperation
                    && \count($operation->getUriVariables() ?? []) > 1
                )
            ) {
                throw new InvalidArgumentException('Cannot find object to populate, use JSON-LD or specify an IRI at path "id".');
            }
            $uriVariables = $this->getContextUriVariables($data, $operation, $context);
            $iri = $this->iriConverter->getIriFromResource($context['resource_class'], UrlGeneratorInterface::ABS_PATH, $operation, ['uri_variables' => $uriVariables]);

            $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] = $this->iriConverter->getResourceFromIri($iri, $context + ['fetch_data' => true]);
        }

        return false;
    }

    private function getContextUriVariables(array $data, Operation $operation, array $context): array
    {
        $uriVariables = $context['uri_variables'] ?? [];

        if ($operation instanceof HttpOperation) {
            $operationUriVariables = $operation->getUriVariables();
            if ((null !== $uriVariable = array_shift($operationUriVariables)) && \count($uriVariable->getIdentifiers())) {
                $identifier = $uriVariable->getIdentifiers()[0];
                if (isset($data[$identifier])) {
                    $uriVariables[$uriVariable->getParameterName()] = $data[$identifier];
                }
            }
        }

        return $uriVariables;
    }
}
