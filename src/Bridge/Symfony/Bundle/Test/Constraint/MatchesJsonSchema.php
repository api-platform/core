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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Constraint;

use JsonSchema\Validator;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * Asserts that a JSON document matches a given JSON Schema.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class MatchesJsonSchema extends Constraint
{
    private $schema;
    private $checkMode;

    /**
     * @param array|string $schema
     */
    public function __construct($schema, ?int $checkMode = null)
    {
        $this->checkMode = $checkMode;
        $this->schema = \is_array($schema) ? (object) $schema : json_decode($schema);
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return 'matches the provided JSON Schema';
    }

    /**
     * @param array $other
     */
    protected function matches($other): bool
    {
        if (!class_exists(Validator::class)) {
            throw new \RuntimeException('The "justinrainbow/json-schema" library must be installed to use "assertMatchesJsonSchema()". Try running "composer require --dev justinrainbow/json-schema".');
        }

        $other = (object) $other;

        $validator = new Validator();
        $validator->validate($other, $this->schema, $this->checkMode);

        return $validator->isValid();
    }

    /**
     * @param object $other
     */
    protected function additionalFailureDescription($other): string
    {
        $other = (object) $other;

        $validator = new Validator();
        $validator->check($other, $this->schema);

        $errors = [];
        foreach ($validator->getErrors() as $error) {
            $property = $error['property'] ? $error['property'].': ' : '';
            $errors[] = $property.$error['message'];
        }

        return implode("\n", $errors);
    }
}
