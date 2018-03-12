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

namespace ApiPlatform\Core\GraphQl\Type\Definition;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\LeafType;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\Utils;

/**
 * Represents an union of other input types.
 *
 * @experimental
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class InputUnionType extends Type implements InputType, LeafType
{
    /**
     * @var InputObjectType[]
     */
    private $types;

    /**
     * @var array
     */
    private $config;

    /**
     * @throws InvariantViolation
     */
    public function __construct(array $config)
    {
        if (!isset($config['name'])) {
            $config['name'] = $this->tryInferName();
        }

        Utils::assertValidName($config['name']);

        $this->name = $config['name'];
        $this->description = $config['description'] ?? null;
        $this->config = $config;
    }

    /**
     * @throws InvariantViolation
     *
     * @return InputObjectType[]
     */
    public function getTypes(): array
    {
        if (null !== $this->types) {
            return $this->types;
        }

        if (($types = $this->config['types'] ?? null) && \is_callable($types)) {
            $types = \call_user_func($this->config['types']);
        }

        if (!\is_array($types)) {
            throw new InvariantViolation(
                "{$this->name} types must be an Array or a callable which returns an Array."
            );
        }

        return $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function assertValid()
    {
        parent::assertValid();

        $types = $this->getTypes();
        Utils::invariant(\count($types) > 0, "{$this->name} types must not be empty");

        $includedTypeNames = [];
        foreach ($types as $inputType) {
            Utils::invariant(
                $inputType instanceof InputType,
                "{$this->name} may only contain input types, it cannot contain: %s.",
                Utils::printSafe($inputType)
            );
            Utils::invariant(
                !isset($includedTypeNames[$inputType->name]),
                "{$this->name} can include {$inputType->name} type only once."
            );
            $includedTypeNames[$inputType->name] = true;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvariantViolation
     */
    public function serialize($value)
    {
        foreach ($this->getTypes() as $type) {
            if ($type instanceof LeafType) {
                try {
                    return $type->serialize($value);
                } catch (\Exception $e) {
                }
            }
        }

        throw new InvariantViolation(sprintf('Types in union cannot represent value: %s', Utils::printSafe($value)));
    }

    /**
     * {@inheritdoc}
     *
     * @throws Error
     */
    public function parseValue($value)
    {
        foreach ($this->getTypes() as $type) {
            if ($type instanceof LeafType) {
                try {
                    return $type->parseValue($value);
                } catch (\Exception $e) {
                }
            }
        }

        throw new Error(sprintf('Types in union cannot represent value: %s', Utils::printSafeJson($value)));
    }

    /**
     * {@inheritdoc}
     */
    public function parseLiteral($valueNode)
    {
        foreach ($this->getTypes() as $type) {
            if ($type instanceof LeafType && null !== $parsed = $type->parseLiteral($valueNode)) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isValidValue($value): bool
    {
        foreach ($this->getTypes() as $type) {
            if ($type instanceof LeafType && $type->isValidValue($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isValidLiteral($valueNode): bool
    {
        foreach ($this->getTypes() as $type) {
            if ($type instanceof LeafType && $type->isValidLiteral($valueNode)) {
                return true;
            }
        }

        return false;
    }
}
