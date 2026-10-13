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

namespace ApiPlatform\Laravel\State;

use ApiPlatform\Laravel\ApiResource\ValidationError;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\DenormalizationViolationFactoryInterface;
use Illuminate\Contracts\Validation\Rule as LaravelRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;

/**
 * Laravel-flavored denormalization violation factory — translates Symfony serializer
 * type errors into a 422 {@see ValidationError} when the Operation's Laravel rules
 * describe the property.
 *
 * Reads rules declared on the operation (string|array form, e.g. `'required|string'`
 * or `['required', 'string']`). FormRequest-class rules and pure-callable rule sets
 * are intentionally skipped in v1: a FormRequest-based contract typically runs in the
 * validation phase against the raw request, not the denormalized body.
 *
 * Mapping:
 *
 * | Exception "current type" | Matching Laravel rule                       | Emitted code   |
 * |--------------------------|---------------------------------------------|----------------|
 * | null                     | required, filled                            | blank          |
 * | null                     | present                                     | null           |
 * | any wrong type           | string, integer, int, numeric, boolean,     | invalid_type   |
 * |                          | bool, array, date, json                     |                |
 * | any wrong type           | any other rule (no `nullable`)              | invalid_type   |
 * | null                     | nullable (no required/present/filled)       | (no match)     |
 * | any                      | (no rule)                                   | (no match)     |
 *
 * In collect mode, unconstrained errors still emit a generic `invalid_type` entry so
 * the response surface stays consistent with prior behavior.
 *
 * Codes are plain semantic strings — the Laravel package does not depend on Symfony
 * Validator.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 */
final class DenormalizationViolationFactory implements DenormalizationViolationFactoryInterface
{
    public const CODE_BLANK = 'blank';
    public const CODE_NULL = 'null';
    public const CODE_INVALID_TYPE = 'invalid_type';

    private const REQUIRED_RULES = ['required' => true, 'filled' => true];
    private const PRESENT_RULES = ['present' => true];

    public function handle(NotNormalizableValueException|PartialDenormalizationException $exception, Operation $operation): void
    {
        if ($exception instanceof NotNormalizableValueException) {
            $violation = $this->buildViolation($exception, $operation);
            if (null === $violation) {
                return;
            }

            throw new ValidationError($violation['message'], $this->makeId([$violation['propertyPath']]), $exception, [$violation]);
        }

        $violations = [];
        $errors = method_exists($exception, 'getNotNormalizableValueErrors') ? $exception->getNotNormalizableValueErrors() : $exception->getErrors();
        foreach ($errors as $error) {
            if (!$error instanceof NotNormalizableValueException) {
                continue;
            }
            $violations[] = $this->buildViolation($error, $operation) ?? $this->buildGenericViolation($error);
        }

        if (!$violations) {
            return;
        }

        $paths = array_filter(array_map(static fn (array $v): string => $v['propertyPath'], $violations));
        $message = implode('; ', array_map(static fn (array $v): string => $v['propertyPath'].': '.$v['message'], $violations));

        throw new ValidationError($message, $this->makeId($paths), $exception, $violations);
    }

    /**
     * @return array{propertyPath: string, message: string, code: string}|null
     */
    private function buildViolation(NotNormalizableValueException $exception, Operation $operation): ?array
    {
        $rules = $operation->getRules();
        if (\is_callable($rules)) {
            $rules = $rules();
        }

        if (\is_string($rules) && is_a($rules, FormRequest::class, true)) {
            return null;
        }

        if (!\is_array($rules)) {
            return null;
        }

        $path = $exception->getPath();
        if (null === $path || '' === $path || !\array_key_exists($path, $rules)) {
            return null;
        }

        $propertyRules = $this->extractRuleTokens($rules[$path]);
        if (!$propertyRules) {
            return null;
        }

        $isNull = 'null' === strtolower((string) $exception->getCurrentType());

        if ($isNull) {
            $hasRequired = (bool) array_intersect_key(self::REQUIRED_RULES, $propertyRules);
            $hasPresent = (bool) array_intersect_key(self::PRESENT_RULES, $propertyRules);

            // `nullable` explicitly permits null when no required/present/filled is set.
            if (isset($propertyRules['nullable']) && !$hasRequired && !$hasPresent) {
                return null;
            }

            if ($hasRequired) {
                return $this->violation($path, 'This value should not be blank.', self::CODE_BLANK);
            }
            if ($hasPresent) {
                return $this->violation($path, 'This value should not be null.', self::CODE_NULL);
            }
        }

        return $this->violation($path, $this->typeMessage($exception), self::CODE_INVALID_TYPE);
    }

    /**
     * @return array<string, true> rule tokens as a keyed map for O(1) lookup
     */
    private function extractRuleTokens(mixed $raw): array
    {
        if (\is_string($raw)) {
            $items = explode('|', $raw);
        } elseif (\is_array($raw)) {
            $items = $raw;
        } else {
            return [];
        }

        $tokens = [];
        foreach ($items as $item) {
            if ($item instanceof LaravelRule || $item instanceof ValidationRule || \is_object($item)) {
                continue;
            }
            if (!\is_string($item)) {
                continue;
            }
            $name = strtolower(strstr($item, ':', true) ?: $item);
            if ('' === $name) {
                continue;
            }
            $tokens[$name] = true;
        }

        return $tokens;
    }

    /**
     * @return array{propertyPath: string, message: string, code: string}
     */
    private function violation(string $path, string $message, string $code): array
    {
        return [
            'propertyPath' => $path,
            'message' => $message,
            'code' => $code,
        ];
    }

    /**
     * @return array{propertyPath: string, message: string, code: string}
     */
    private function buildGenericViolation(NotNormalizableValueException $exception): array
    {
        return $this->violation(
            (string) $exception->getPath(),
            $exception->canUseMessageForUser() ? $exception->getMessage() : $this->typeMessage($exception),
            self::CODE_INVALID_TYPE,
        );
    }

    private function typeMessage(NotNormalizableValueException $exception): string
    {
        $expectedTypes = $exception->getExpectedTypes() ?? [];
        if (!$expectedTypes) {
            return 'This value should be of the right type.';
        }

        return \sprintf('This value should be of type %s.', implode('|', $expectedTypes));
    }

    /**
     * @param string[] $paths
     */
    private function makeId(array $paths): string
    {
        return hash('xxh3', implode(',', $paths) ?: 'denormalization');
    }
}
