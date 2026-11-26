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

namespace ApiPlatform\JsonLd\Serializer;

use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use ApiPlatform\Serializer\AbstractItemNormalizer;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

/**
 * Shared denormalization logic for the JSON-LD item (de)normalizer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
trait ItemNormalizerTrait
{
    private const JSONLD_KEYWORDS = [
        '@context',
        '@direction',
        '@graph',
        '@id',
        '@import',
        '@included',
        '@index',
        '@json',
        '@language',
        '@list',
        '@nest',
        '@none',
        '@prefix',
        '@propagate',
        '@protected',
        '@reverse',
        '@set',
        '@type',
        '@value',
        '@version',
        '@vocab',
    ];

    private const ACTIVE_CONTEXT = 'jsonld_active_context';

    /**
     * @throws NotNormalizableValueException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['@id']) && !\is_string($data['@id']) && (!isset($context[AbstractItemNormalizer::OBJECT_TO_POPULATE]) || ($context['deep_object_to_populate'] ?? false))) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "@id" attribute must be "string", "%s" given.', \gettype($data['@id'])), $data['@id'], ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'].'.@id' : '@id', true);
        }

        if (\is_array($data) && \array_key_exists('@context', $data)) {
            $context[self::ACTIVE_CONTEXT] = $this->processLocalContext($context[self::ACTIVE_CONTEXT] ?? [], $data['@context']);
        }

        // Avoid issues with proxies if we populated the object
        if (isset($data['@id']) && !isset($context[AbstractItemNormalizer::OBJECT_TO_POPULATE])) {
            if (true !== ($context['api_allow_update'] ?? true)) {
                throw new NotNormalizableValueException('Update is not allowed for this operation.');
            }

            try {
                $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] = $this->iriConverter->getResourceFromIri($data['@id'], $context + ['fetch_data' => true], $context['operation'] ?? null);
            } catch (ItemNotFoundException $e) {
                $operation = $context['operation'] ?? null;

                if (!('PUT' === $operation?->getMethod() && ($operation->getExtraProperties()['standard_put'] ?? true))) {
                    throw $e;
                }
            }
        } elseif (isset($data['@id']) && ($context['deep_object_to_populate'] ?? false)) {
            // the object to populate is the relation currently linked to the parent, an explicit @id must replace it instead of mutating it in place
            $context[AbstractItemNormalizer::OBJECT_TO_POPULATE] = $this->iriConverter->getResourceFromIri($data['@id'], $context + ['fetch_data' => true], $context['operation'] ?? null);
        }

        return parent::denormalize($data, $type, $format, $context);
    }

    protected function getClassDiscriminatorResolvedClass(array $data, string $class, array $context = []): string
    {
        $mapping = $this->classDiscriminatorResolver?->getMappingForClass($class);
        if ('@type' !== $mapping?->getTypeProperty() || !isset($data['@type']) || \is_string($data['@type']) && null !== $mapping->getClassForType($data['@type'])) {
            return parent::getClassDiscriminatorResolvedClass($data, $class, $context);
        }

        $activeContext = $context[self::ACTIVE_CONTEXT] ?? [];
        $path = isset($context['deserialization_path']) ? $context['deserialization_path'].'.@type' : '@type';
        $types = \is_array($data['@type']) && array_is_list($data['@type']) ? $data['@type'] : [$data['@type']];

        foreach ($types as $type) {
            if (!\is_string($type)) {
                throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The "@type" of the abstract object "%s" must be a string or an array of strings.', $class), $data['@type'], ['string', 'array'], $path);
            }

            $expandedType = $this->expandIri($type, $activeContext);
            foreach ($mapping->getTypesMapping() as $mappedType => $mappedClass) {
                if ($expandedType === $this->expandIri((string) $mappedType, $activeContext)) {
                    return $mappedClass;
                }
            }
        }

        throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type "%s" is not a valid value.', implode('", "', $types)), $data['@type'], ['string'], $path, true);
    }

    /**
     * Folds a local @context into the active one; remote contexts are not dereferenced.
     *
     * @param array{'@vocab'?: string, terms?: array<string, string>} $activeContext
     *
     * @return array{'@vocab'?: string, terms?: array<string, string>}
     */
    private function processLocalContext(array $activeContext, mixed $localContext): array
    {
        if (null === $localContext) {
            return [];
        }

        if (!\is_array($localContext)) {
            return $activeContext;
        }

        if (array_is_list($localContext)) {
            foreach ($localContext as $context) {
                $activeContext = $this->processLocalContext($activeContext, $context);
            }

            return $activeContext;
        }

        foreach ($localContext as $key => $definition) {
            if ('@vocab' === $key) {
                if (\is_string($definition)) {
                    $activeContext['@vocab'] = $this->expandIri($definition, $activeContext);
                } else {
                    unset($activeContext['@vocab']);
                }

                continue;
            }

            if (str_starts_with((string) $key, '@')) {
                continue;
            }

            $iri = \is_array($definition) ? $definition['@id'] ?? null : $definition;
            if (\is_string($iri)) {
                $activeContext['terms'][$key] = $iri;
            } else {
                unset($activeContext['terms'][$key]);
            }
        }

        return $activeContext;
    }

    /**
     * @param array{'@vocab'?: string, terms?: array<string, string>} $activeContext
     */
    private function expandIri(string $value, array $activeContext, int $depth = 0): string
    {
        // guards against cyclic term definitions
        if ($depth > 8 || str_starts_with($value, '@')) {
            return $value;
        }

        if (isset($activeContext['terms'][$value]) && $activeContext['terms'][$value] !== $value) {
            return $this->expandIri($activeContext['terms'][$value], $activeContext, $depth + 1);
        }

        if (str_contains($value, ':')) {
            [$prefix, $suffix] = explode(':', $value, 2);
            if ('_' === $prefix || str_starts_with($suffix, '//') || !isset($activeContext['terms'][$prefix])) {
                return $value;
            }

            return $this->expandIri($activeContext['terms'][$prefix], $activeContext, $depth + 1).$suffix;
        }

        return isset($activeContext['@vocab']) ? $activeContext['@vocab'].$value : $value;
    }

    protected function getAllowedAttributes(string|object $classOrObject, array $context, bool $attributesAsString = false): array|bool
    {
        $allowedAttributes = parent::getAllowedAttributes($classOrObject, $context, $attributesAsString);
        if (\is_array($allowedAttributes) && ($context['api_denormalize'] ?? false)) {
            $allowedAttributes = array_merge($allowedAttributes, self::JSONLD_KEYWORDS);
        }

        return $allowedAttributes;
    }
}
