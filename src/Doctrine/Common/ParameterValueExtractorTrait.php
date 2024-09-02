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

namespace ApiPlatform\Doctrine\Common;

use ApiPlatform\Metadata\Parameter;

trait ParameterValueExtractorTrait
{
    /**
     * @return array<string, mixed>
     */
    private function extractParameterValue(Parameter $parameter, mixed $value): array
    {
        $key = $parameter->getProperty() ?? $parameter->getKey();
        if (!str_contains($key, ':property')) {
            return [$key => $value];
        }

        return [str_replace('[:property]', '', $key) => $value];
    }
}
