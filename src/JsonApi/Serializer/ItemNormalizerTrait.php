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

namespace ApiPlatform\JsonApi\Serializer;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

/**
 * Shared support gates and denormalization logic for the JSON:API item (de)normalizer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
trait ItemNormalizerTrait
{
    public function getSupportedTypes(?string $format): array
    {
        return self::FORMAT === $format ? parent::getSupportedTypes($format) : [];
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return self::FORMAT === $format && parent::supportsDenormalization($data, $type, $format, $context);
    }

    /**
     * @throws NotNormalizableValueException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // When re-entering for input DTO denormalization, data has already been
        // unwrapped from the JSON:API structure by the first pass. Skip extraction.
        if (isset($context['api_platform_input'])) {
            return parent::denormalize($data, $type, $format, $context);
        }

        // Avoid issues with proxies if we populated the object
        if (!isset($context[AbstractItemNormalizer::OBJECT_TO_POPULATE]) && isset($data['data']['id'])) {
            if (true !== ($context['api_allow_update'] ?? true)) {
                throw new NotNormalizableValueException('Update is not allowed for this operation.');
            }

            $context += ['fetch_data' => false];
            if ($this->useIriAsId) {
                $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] = $this->iriConverter->getResourceFromIri($data['data']['id'], $context);
            } else {
                $operation = $context['operation'] ?? null;
                if ($operation instanceof HttpOperation) {
                    $iri = $this->reconstructIri($type, (string) $data['data']['id'], $operation);
                    $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] = $this->iriConverter->getResourceFromIri($iri, $context);
                }
            }
        }

        $dataToDenormalize = array_merge(
            $data['data']['attributes'] ?? [],
            $data['data']['relationships'] ?? []
        );

        return parent::denormalize($dataToDenormalize, $type, $format, $context);
    }

    protected function isAllowedAttribute(object|string $classOrObject, string $attribute, ?string $format = null, array $context = []): bool
    {
        return preg_match('/^\\w[-\\w_]*$/', $attribute) && parent::isAllowedAttribute($classOrObject, $attribute, $format, $context);
    }

    protected function setAttributeValue(object $object, string $attribute, mixed $value, ?string $format = null, array $context = []): void
    {
        parent::setAttributeValue($object, $attribute, \is_array($value) && \array_key_exists('data', $value) ? $value['data'] : $value, $format, $context);
    }

    /**
     * @see http://jsonapi.org/format/#document-resource-object-linkage
     *
     * @throws RuntimeException
     * @throws UnexpectedValueException
     */
    protected function denormalizeRelation(string $attributeName, ApiProperty $propertyMetadata, string $className, mixed $value, ?string $format, array $context): ?object
    {
        if (!\is_array($value) || !isset($value['id'], $value['type'])) {
            throw new UnexpectedValueException('Only resource linkage supported currently, see: http://jsonapi.org/format/#document-resource-object-linkage.');
        }

        try {
            $context += ['fetch_data' => true];
            if ($this->useIriAsId) {
                return $this->iriConverter->getResourceFromIri($value['id'], $context);
            }

            /** @var HttpOperation $getOperation */
            $getOperation = $this->resourceMetadataCollectionFactory->create($className)->getOperation(httpOperation: true);
            $iri = $this->reconstructIri($className, (string) $value['id'], $getOperation);

            return $this->iriConverter->getResourceFromIri($iri, $context);
        } catch (ItemNotFoundException $e) {
            if (!isset($context['not_normalizable_value_exceptions'])) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
            $context['not_normalizable_value_exceptions'][] = NotNormalizableValueException::createForUnexpectedDataType(
                $e->getMessage(),
                $value,
                [$className],
                $context['deserialization_path'] ?? null,
                true,
                $e->getCode(),
                $e
            );

            return null;
        }
    }

    /**
     * Maps the id to the operation's single URI variable parameter and generates the IRI.
     * Composite identifiers on a single Link work naturally since the composite string
     * (e.g. "field1=val1;field2=val2") is passed as-is.
     */
    private function reconstructIri(string $resourceClass, string $id, HttpOperation $operation): string
    {
        $uriVariables = $operation->getUriVariables() ?? [];

        if (\count($uriVariables) > 1) {
            throw new UnexpectedValueException(\sprintf('JSON:API entity identifier mode requires operations with a single URI variable, operation "%s" has %d. Consider adding a NotExposed Get operation on the resource.', $operation->getName() ?? $operation->getUriTemplate(), \count($uriVariables)));
        }

        $parameterName = array_key_first($uriVariables) ?? 'id';

        return $this->iriConverter->getIriFromResource($resourceClass, UrlGeneratorInterface::ABS_PATH, $operation, ['uri_variables' => [$parameterName => $id]]);
    }
}
