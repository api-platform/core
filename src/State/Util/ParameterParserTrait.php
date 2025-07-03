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

use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\HeaderParameterInterface;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\State\ParameterNotFound;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\UnionType;

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
     */
    private function extractParameterValues(Parameter $parameter, array $values): mixed
    {
        $accessors = null;
        $key = $parameter->getKey();
        if (null === $key) {
            throw new \RuntimeException('A Parameter should have a key.');
        }

        if ($parameter instanceof HeaderParameterInterface) {
            $key = strtolower($key);
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
        foreach ($accessors ?? [] as $accessor) {
            if (\is_array($value) && isset($value[$accessor])) {
                $value = $value[$accessor];
            } else {
                $value = new ParameterNotFound();
            }
        }

        if ($value instanceof ParameterNotFound) {
            return $value;
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

        if ($isCollection && true === $parameter->getCastToArray() && !\is_array($value)) {
            $value = [$value];
        }

        if (!$isCollection && $parameter instanceof HeaderParameter && \is_array($value) && array_is_list($value) && 1 === \count($value)) {
            $value = $value[0];
        }

        if (true === $parameter->getCastToNativeType() && ($castFn = $parameter->getCastFn())) {
            if (\is_array($value)) {
                $value = array_map(fn ($v) => $castFn($v, $parameter), $value);
            } else {
                $value = $castFn($value, $parameter);
            }
        }

        return $value;
    }
}
