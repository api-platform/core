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

namespace ApiPlatform\Validator\Util;

use ApiPlatform\Metadata\Parameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\DivisibleBy;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Sequentially;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Unique;

/**
 * Helper to get a set of validation constraints for a given Parameter.
 */
trait ParameterValidationConstraints
{
    /**
     * @param Parameter $parameter readonly
     *
     * @return list<Constraint>
     */
    public static function getParameterValidationConstraints(Parameter $parameter, ?array $schema = null, ?bool $required = null, ?OpenApiParameter $openApi = null): array
    {
        $schema ??= $parameter->getSchema();
        $required ??= $parameter->getRequired() ?? false;
        $openApi ??= $parameter->getOpenApi();

        // When it's an array of openapi parameters take the first one as it's probably just a variant of the query parameter,
        // only getAllowEmptyValue is used here anyways
        if (\is_array($openApi)) {
            $openApi = $openApi[0];
        } elseif (false === $openApi) {
            $openApi = null;
        }

        $assertions = [];
        $allowEmptyValue = $openApi?->getAllowEmptyValue();
        if (false === ($allowEmptyValue ?? $openApi?->getAllowEmptyValue())) {
            $assertions[] = new NotBlank(allowNull: !$required);
        }

        $minimum = $schema['exclusiveMinimum'] ?? $schema['minimum'] ?? null;
        $exclusiveMinimum = isset($schema['exclusiveMinimum']);
        $maximum = $schema['exclusiveMaximum'] ?? $schema['maximum'] ?? null;
        $exclusiveMaximum = isset($schema['exclusiveMaximum']);

        if ($minimum && $maximum) {
            if (!$exclusiveMinimum && !$exclusiveMaximum) {
                $assertions[] = new Range(min: $minimum, max: $maximum);
            } else {
                $assertions[] = $exclusiveMinimum ? new GreaterThan(value: $minimum) : new GreaterThanOrEqual(value: $minimum);
                $assertions[] = $exclusiveMaximum ? new LessThan(value: $maximum) : new LessThanOrEqual(value: $maximum);
            }
        } elseif ($minimum) {
            $assertions[] = $exclusiveMinimum ? new GreaterThan(value: $minimum) : new GreaterThanOrEqual(value: $minimum);
        } elseif ($maximum) {
            $assertions[] = $exclusiveMaximum ? new LessThan(value: $maximum) : new LessThanOrEqual(value: $maximum);
        }

        if (isset($schema['pattern'])) {
            $assertions[] = new Regex('#'.$schema['pattern'].'#');
        }

        if (isset($schema['maxLength']) || isset($schema['minLength'])) {
            $assertions[] = new Length(min: $schema['minLength'] ?? null, max: $schema['maxLength'] ?? null);
        }

        if (isset($schema['multipleOf'])) {
            $assertions[] = new DivisibleBy(value: $schema['multipleOf']);
        }

        if (isset($schema['enum'])) {
            $assertions[] = new Choice(choices: $schema['enum']);
        }

        if ($properties = $parameter->getExtraProperties()['_properties'] ?? []) {
            $fields = [];
            foreach ($properties as $propertyName) {
                $fields[$propertyName] = $assertions;
            }

            return [new Collection(fields: $fields, allowMissingFields: true)];
        }

        $isCollectionType = fn ($t) => $t instanceof CollectionType;
        $isCollection = $parameter->getNativeType()?->isSatisfiedBy($isCollectionType) ?? false;

        // type-info 7.2
        if (!$isCollection && $parameter->getNativeType() instanceof UnionType) {
            foreach ($parameter->getNativeType()->getTypes() as $t) {
                if ($isCollection = $t->isSatisfiedBy($isCollectionType)) {
                    break;
                }
            }
        }

        if ($isCollection) {
            if (true === ($parameter->getCastToArray() ?? false)) {
                $assertions = $assertions ? [new All($assertions)] : [];
            } else {
                $assertions = $assertions ? [new AtLeastOneOf([new Sequentially($assertions), new All($assertions)])] : [];
            }
        }

        if ($required && false !== $allowEmptyValue) {
            $assertions[] = new NotNull(message: \sprintf('The parameter "%s" is required.', $parameter->getKey()));
        }

        if (isset($schema['minItems']) || isset($schema['maxItems'])) {
            $assertions[] = new Count(min: $schema['minItems'] ?? null, max: $schema['maxItems'] ?? null);
        }

        if ($schema['uniqueItems'] ?? false) {
            $assertions[] = new Unique();
        }

        if (isset($schema['type']) && 'array' === $schema['type']) {
            $assertions[] = new Type(type: 'array');
        }

        if (isset($schema['type']) && $parameter->getCastToNativeType()) {
            $assertion = match ($schema['type']) {
                'boolean', 'integer' => new Type(type: $schema['type']),
                'number' => new Type(type: 'float'),
                default => null,
            };

            if ($assertion) {
                $assertions[] = $assertion;
            }
        }

        return $assertions;
    }
}
