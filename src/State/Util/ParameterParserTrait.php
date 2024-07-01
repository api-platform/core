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
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
trait ParameterParserTrait
{
    /**
     * @param array<string, mixed> $values
     */
    private function getParameterFlattenKey(string $key, array $values): string
    {
        $parsedKey = explode('[:property]', $key);

        if (isset($parsedKey[0]) && isset($values[$parsedKey[0]])) {
            return $parsedKey[0];
        }

        return $key;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function extractParameterValues(Parameter $parameter, ?Request $request, array $context): array
    {
        if ($request) {
            return ($parameter instanceof HeaderParameterInterface ? $request->attributes->get('_api_header_parameters') : $request->attributes->get('_api_query_parameters')) ?? [];
        }

        // GraphQl
        return $context['args'] ?? [];
    }
}
