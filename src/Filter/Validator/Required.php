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

namespace ApiPlatform\Core\Filter\Validator;

use Symfony\Component\HttpFoundation\Request;

class Required implements ValidatorInterface
{
    public function validate(string $name, array $filterDescription, Request $request): array
    {
        // filter is not required, the `checkRequired` method can not break
        if (!($filterDescription['required'] ?? false)) {
            return [];
        }

        // if query param is not given, then break
        if (!$this->requestHasQueryParameter($request, $name)) {
            return [
                sprintf('Query parameter "%s" is required', $name),
            ];
        }

        // if query param is empty and the configuration does not allow it
        if (!($filterDescription['swagger']['allowEmptyValue'] ?? false) && empty($this->requestGetQueryParameter($request, $name))) {
            return [
                sprintf('Query parameter "%s" does not allow empty value', $name),
            ];
        }

        return [];
    }

    /**
     * Test if request has required parameter.
     */
    private function requestHasQueryParameter(Request $request, string $name): bool
    {
        $matches = [];
        parse_str($name, $matches);
        if (!$matches) {
            return false;
        }

        $rootName = array_keys($matches)[0] ?? '';
        if (!$rootName) {
            return false;
        }

        if (\is_array($matches[$rootName])) {
            $keyName = array_keys($matches[$rootName])[0];

            $queryParameter = $request->query->get($rootName);

            return \is_array($queryParameter) && isset($queryParameter[$keyName]);
        }

        return $request->query->has($rootName);
    }

    /**
     * Test if required filter is valid. It validates array notation too like "required[bar]".
     */
    private function requestGetQueryParameter(Request $request, string $name)
    {
        $matches = [];
        parse_str($name, $matches);
        if (empty($matches)) {
            return null;
        }

        $rootName = array_keys($matches)[0] ?? '';
        if (!$rootName) {
            return null;
        }

        if (\is_array($matches[$rootName])) {
            $keyName = array_keys($matches[$rootName])[0];

            $queryParameter = $request->query->get($rootName);

            if (\is_array($queryParameter) && isset($queryParameter[$keyName])) {
                return $queryParameter[$keyName];
            }

            return null;
        }

        return $request->query->get($rootName);
    }
}
