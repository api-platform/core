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

use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Common features regarding Constraint Violation normalization.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @internal
 */
abstract class AbstractConstraintViolationListNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public const FORMAT = null; // Must be overridden

    private readonly ?array $serializePayloadFields;

    public function __construct(?array $serializePayloadFields = null, private readonly ?NameConverterInterface $nameConverter = null)
    {
        $this->serializePayloadFields = null === $serializePayloadFields ? null : array_flip($serializePayloadFields);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return static::FORMAT === $format && $data instanceof ConstraintViolationListInterface;
    }

    public function getSupportedTypes($format): array
    {
        return $format === static::FORMAT ? [ConstraintViolationListInterface::class => true] : [];
    }

    public function hasCacheableSupportsMethod(): bool
    {
        if (method_exists(Serializer::class, 'getSupportedTypes')) {
            trigger_deprecation(
                'api-platform/core',
                '3.1',
                'The "%s()" method is deprecated, use "getSupportedTypes()" instead.',
                __METHOD__
            );
        }

        return true;
    }

    protected function getMessagesAndViolations(ConstraintViolationListInterface $constraintViolationList): array
    {
        $violations = $messages = [];

        foreach ($constraintViolationList as $violation) {
            $class = \is_object($root = $violation->getRoot()) ? $root::class : null;

            if ($this->nameConverter instanceof AdvancedNameConverterInterface) {
                $propertyPath = $this->nameConverter->normalize($violation->getPropertyPath(), $class, static::FORMAT);
            } elseif ($this->nameConverter instanceof NameConverterInterface) {
                $propertyPath = $this->nameConverter->normalize($violation->getPropertyPath());
            } else {
                $propertyPath = $violation->getPropertyPath();
            }

            $violationData = [
                'propertyPath' => $propertyPath,
                'message' => $violation->getMessage(),
                'code' => $violation->getCode(),
            ];

            if ($hint = $violation->getParameters()['hint'] ?? false) {
                $violationData['hint'] = $hint;
            }

            $constraint = $violation instanceof ConstraintViolation ? $violation->getConstraint() : null;
            if (
                [] !== $this->serializePayloadFields
                && $constraint
                && $constraint->payload
                // If some fields are whitelisted, only them are added
                && $payloadFields = null === $this->serializePayloadFields ? $constraint->payload : array_intersect_key($constraint->payload, $this->serializePayloadFields)
            ) {
                $violationData['payload'] = $payloadFields;
            }

            $violations[] = $violationData;
            $messages[] = ($violationData['propertyPath'] ? "{$violationData['propertyPath']}: " : '').$violationData['message'];
        }

        return [$messages, $violations];
    }
}
