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

namespace ApiPlatform\Symfony\Bundle\Test\Constraint;

use JsonSchema\Validator;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Asserts that a JSON document matches a given JSON Schema.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class MatchesJsonSchema extends Constraint
{
    private object|array $schema;

    public function __construct(object|array|string $schema, private readonly ?int $checkMode = null)
    {
        $this->schema = \is_string($schema) ? json_decode($schema, null, 512, \JSON_THROW_ON_ERROR) : $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return 'matches the provided JSON Schema';
    }

    /**
     * {@inheritdoc}
     */
    protected function matches(mixed $other): bool
    {
        if (!class_exists(Validator::class)) {
            throw new \LogicException('The "justinrainbow/json-schema" library must be installed to use "assertMatchesJsonSchema()". Try running "composer require --dev justinrainbow/json-schema".');
        }

        $other = $this->normalizeJson($other);

        $validator = new Validator();
        $validator->validate($other, $this->schema, $this->checkMode);

        return $validator->isValid();
    }

    /**
     * {@inheritdoc}
     */
    protected function additionalFailureDescription(mixed $other): string
    {
        $other = $this->normalizeJson($other);

        $validator = new Validator();
        $validator->validate($other, $this->schema, $this->checkMode);

        $errors = [];
        foreach ($validator->getErrors() as $error) {
            $property = $error['property'] ? $error['property'].': ' : '';
            $errors[] = $property.$error['message'];
        }

        return implode("\n", $errors);
    }

    /**
     * Normalizes a JSON document.
     *
     * Specifically, we should ensure that:
     * 1. a JSON object is represented as a PHP object, not as an associative array.
     */
    private function normalizeJson(mixed $document): object|array
    {
        if (\is_scalar($document) || \is_object($document)) {
            return $document;
        }

        if (!\is_array($document)) {
            throw new \InvalidArgumentException('Document must be scalar, array or object.');
        }

        $document = json_encode($document, \JSON_THROW_ON_ERROR);
        $document = json_decode($document, null, 512, \JSON_THROW_ON_ERROR);
        if (!\is_array($document) && !\is_object($document)) {
            throw new \UnexpectedValueException('JSON decode failed.');
        }

        return $document;
    }
}
