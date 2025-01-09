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

namespace ApiPlatform\ParameterValidator\Validator;

/**
 * @deprecated use \ApiPlatform\Metadata\Parameter::$constraints instead
 */
final class Pattern implements ValidatorInterface
{
    use CheckFilterDeprecationsTrait;

    public function __construct()
    {
        trigger_deprecation('api-platform/core', '3.4', 'The class "%s" is deprecated, use "\ApiPlatform\Metadata\Parameter::$constraints" instead.', __CLASS__);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $name, array $filterDescription, array $queryParameters): array
    {
        $value = $queryParameters[$name] ?? null;
        if (empty($value) && '0' !== $value || !\is_string($value)) {
            return [];
        }

        $this->checkFilterDeprecations($filterDescription);

        $pattern = $filterDescription['openapi']['pattern'] ?? $filterDescription['swagger']['pattern'] ?? null;

        if (null !== $pattern && !preg_match($pattern, $value)) {
            return [
                \sprintf('Query parameter "%s" must match pattern %s', $name, $pattern),
            ];
        }

        return [];
    }
}
