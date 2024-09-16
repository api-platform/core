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

namespace ApiPlatform\State\Util;

use ApiPlatform\Metadata\HeaderParameterInterface;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterNotFound;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
trait ParameterParserTrait
{
    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function getParameterValues(Parameter $parameter, ?Request $request, array $context): array
    {
        if ($request) {
            return ($parameter instanceof HeaderParameterInterface ? $request->attributes->get('_api_header_parameters') : $request->attributes->get('_api_query_parameters')) ?? [];
        }

        return $context['args'] ?? [];
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<mixed, mixed>|ParameterNotFound|array
     */
    private function extractParameterValues(Parameter $parameter, array $values): string|ParameterNotFound|array
    {
        $accessors = null;
        $key = $parameter->getKey();
        if (null === $key) {
            throw new \RuntimeException('A Parameter should have a key.');
        }

        $parsedKey = explode('[:property]', $key);
        if (isset($parsedKey[0]) && isset($values[$parsedKey[0]])) {
            $key = $parsedKey[0];
        } elseif (str_contains($key, '[')) {
            preg_match_all('/[^\[\]]+/', $key, $matches);
            $key = array_shift($matches[0]);
            $accessors = $matches[0];
        }

        $value = $values[$key] ?? new ParameterNotFound();
        if (!$accessors) {
            return $value;
        }

        foreach ($accessors as $accessor) {
            if (\is_array($value) && isset($value[$accessor])) {
                $value = $value[$accessor];
            } else {
                $value = new ParameterNotFound();
                continue;
            }
        }

        return $value;
    }
}
