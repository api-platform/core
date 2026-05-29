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

namespace ApiPlatform\Validator;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\DenormalizationViolationFactoryInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\NoSuchMetadataException;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

/**
 * Constraint-aware denormalization violation factory — Symfony Validator flavor.
 *
 * Rule table (see issue #7981):
 *
 * | Exception "current type" | Matching constraint  | Emitted violation                                 |
 * |--------------------------|----------------------|---------------------------------------------------|
 * | null                     | NotBlank             | NotBlank::IS_BLANK_ERROR + constraint message     |
 * | null                     | NotNull              | NotNull::IS_NULL_ERROR  + constraint message      |
 * | any wrong type           | Type                 | Type::INVALID_TYPE_ERROR + constraint message     |
 * | any wrong type           | any other constraint | generic Type violation @ 422                      |
 * | any wrong type           | (no constraint)      | none — single-error path rethrows → 400           |
 *
 * In collect mode (PartialDenormalizationException), unconstrained errors still emit
 * a generic Type violation so the response stays consistent with prior behavior.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class DenormalizationViolationFactory implements DenormalizationViolationFactoryInterface
{
    private TranslatorInterface $translator;

    public function __construct(
        private readonly MetadataFactoryInterface $metadataFactory,
        ?TranslatorInterface $translator = null,
    ) {
        if (null === $translator) {
            $translator = new class implements TranslatorInterface, LocaleAwareInterface {
                use TranslatorTrait;
            };
            $translator->setLocale('en');
        }

        $this->translator = $translator;
    }

    public function handle(NotNormalizableValueException|PartialDenormalizationException $exception, Operation $operation): void
    {
        if ($exception instanceof NotNormalizableValueException) {
            $violation = $this->buildViolation($exception, $operation);
            if (null === $violation) {
                return;
            }

            throw new ValidationException(new ConstraintViolationList([$violation]));
        }

        $violations = new ConstraintViolationList();
        $errors = method_exists($exception, 'getNotNormalizableValueErrors') ? $exception->getNotNormalizableValueErrors() : $exception->getErrors();
        foreach ($errors as $error) {
            if (!$error instanceof NotNormalizableValueException) {
                continue;
            }
            $violations->add($this->buildViolation($error, $operation) ?? $this->buildViolation($error, $operation, true));
        }

        if (\count($violations) > 0) {
            throw new ValidationException($violations);
        }
    }

    /**
     * Returns a violation for the given error.
     *
     * When `$generic` is true, emits a Type-based fallback regardless of property metadata
     * (used in collect mode to keep one violation per error). When false, returns null if
     * no matching constraint is declared on the property — caller rethrows.
     */
    private function buildViolation(NotNormalizableValueException $exception, Operation $operation, bool $generic = false): ?ConstraintViolationInterface
    {
        $path = $exception->getPath();
        if (null === $path || '' === $path) {
            return $generic ? $this->emitViolation($exception, null, (string) Type::INVALID_TYPE_ERROR) : null;
        }

        if ($generic) {
            return $this->emitViolation($exception, null, (string) Type::INVALID_TYPE_ERROR);
        }

        $class = $operation->getClass();
        if (null === $class || (!class_exists($class) && !interface_exists($class))) {
            return null;
        }

        try {
            $classMetadata = $this->metadataFactory->getMetadataFor($class);
        } catch (NoSuchMetadataException) {
            return null;
        }

        if (!$classMetadata instanceof ClassMetadataInterface || !$classMetadata->hasPropertyMetadata($path)) {
            return null;
        }

        $validationGroups = ($operation->getValidationContext() ?? [])['groups'] ?? null;
        $constraints = $this->collectConstraints($classMetadata, $path, $validationGroups);
        if (!$constraints) {
            return null;
        }

        $isNull = 'null' === strtolower((string) $exception->getCurrentType());

        if ($isNull) {
            if (isset($constraints[NotBlank::class])) {
                return $this->emitViolation($exception, $constraints[NotBlank::class], (string) NotBlank::IS_BLANK_ERROR);
            }
            if (isset($constraints[NotNull::class])) {
                return $this->emitViolation($exception, $constraints[NotNull::class], (string) NotNull::IS_NULL_ERROR);
            }
        }

        if (isset($constraints[Type::class])) {
            return $this->emitViolation($exception, $constraints[Type::class], (string) Type::INVALID_TYPE_ERROR);
        }

        // Property has constraints but none match by class → still 422 with a generic Type message.
        return $this->emitViolation($exception, new Type([]), (string) Type::INVALID_TYPE_ERROR);
    }

    /**
     * @param array<string>|null $validationGroups
     *
     * @return array<class-string<Constraint>, Constraint> indexed by constraint class; later entries overwrite earlier
     */
    private function collectConstraints(ClassMetadataInterface $classMetadata, string $property, ?array $validationGroups): array
    {
        $groups = $validationGroups ?: [Constraint::DEFAULT_GROUP];
        $constraints = [];

        foreach ($classMetadata->getPropertyMetadata($property) as $propertyMetadata) {
            foreach ($groups as $group) {
                foreach ($propertyMetadata->findConstraints($group) as $constraint) {
                    $constraints[$constraint::class] = $constraint;
                }
            }
        }

        return $constraints;
    }

    private function emitViolation(NotNormalizableValueException $exception, ?Constraint $constraint, string $code): ConstraintViolation
    {
        $parameters = [];
        if ($exception->canUseMessageForUser()) {
            $parameters['hint'] = $exception->getMessage();
        }

        $expectedTypes = $this->normalizeExpectedTypes($exception->getExpectedTypes());

        // No constraint + no expected types + user-friendly message → use the exception message verbatim.
        if (null === $constraint && !$expectedTypes && $exception->canUseMessageForUser()) {
            $message = $exception->getMessage();

            return new ConstraintViolation($message, $message, $parameters, null, $exception->getPath(), null, null, $code);
        }

        $message = $this->resolveMessage($constraint, $expectedTypes);
        $translationParameters = [];
        if ($expectedTypes && str_contains($message, '{{ type }}')) {
            $translationParameters['{{ type }}'] = implode('|', $expectedTypes);
        }

        return new ConstraintViolation(
            $this->translator->trans($message, $translationParameters, 'validators'),
            $message,
            $parameters,
            null,
            $exception->getPath(),
            null,
            null,
            $code,
            $constraint,
        );
    }

    /**
     * @param string[] $expectedTypes
     */
    private function resolveMessage(?Constraint $constraint, array $expectedTypes): string
    {
        if ($constraint instanceof NotBlank || $constraint instanceof NotNull || $constraint instanceof Type) {
            return $constraint->message;
        }

        return (new Type($expectedTypes))->message;
    }

    /**
     * @param string[]|null $expectedTypes
     *
     * @return string[]
     */
    private function normalizeExpectedTypes(?array $expectedTypes): array
    {
        $normalized = [];
        foreach ($expectedTypes ?? [] as $expectedType) {
            if (\is_string($expectedType) && (class_exists($expectedType) || interface_exists($expectedType))) {
                $pos = strrpos($expectedType, '\\');
                $normalized[] = false === $pos ? $expectedType : substr($expectedType, $pos + 1);
                continue;
            }
            $normalized[] = $expectedType;
        }

        return $normalized;
    }
}
