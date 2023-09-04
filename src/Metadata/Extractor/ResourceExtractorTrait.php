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

namespace ApiPlatform\Metadata\Extractor;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Utils for ResourceExtractors.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
trait ResourceExtractorTrait
{
    private function buildArrayValue(\SimpleXMLElement|array|null $resource, string $key, mixed $default = null): ?array
    {
        if (\is_object($resource) && $resource instanceof \SimpleXMLElement) {
            if (!isset($resource->{$key.'s'}->{$key})) {
                return $default;
            }

            return (array) $resource->{$key.'s'}->{$key};
        }

        if (empty($resource[$key])) {
            return $default;
        }

        if (!\is_array($resource[$key])) {
            throw new InvalidArgumentException(sprintf('"%s" setting is expected to be an array, %s given', $key, \gettype($resource[$key])));
        }

        return $resource[$key];
    }

    /**
     * Transforms an attribute's value in a PHP value.
     */
    private function phpize(\SimpleXMLElement|array|null $resource, string $key, string $type, mixed $default = null): array|bool|int|string|null
    {
        if (!isset($resource[$key])) {
            return $default;
        }

        switch ($type) {
            case 'bool|string':
                return \is_bool($resource[$key]) || \in_array((string) $resource[$key], ['1', '0', 'true', 'false'], true) ? $this->phpize($resource, $key, 'bool') : $this->phpize($resource, $key, 'string');
            case 'string':
                return (string) $resource[$key];
            case 'integer':
                return (int) $resource[$key];
            case 'bool':
                if (\is_object($resource) && $resource instanceof \SimpleXMLElement) {
                    return (bool) XmlUtils::phpize($resource[$key]);
                }

                return \in_array($resource[$key], ['1', 'true', 1, true], false);
        }

        throw new InvalidArgumentException(sprintf('The property "%s" must be a "%s", "%s" given.', $key, $type, \gettype($resource[$key])));
    }

    private function buildArgs(\SimpleXMLElement $resource): ?array
    {
        if (!isset($resource->args->arg)) {
            return null;
        }

        $data = [];
        foreach ($resource->args->arg as $arg) {
            $data[(string) $arg['id']] = $this->buildValues($arg->values);
        }

        return $data;
    }

    private function buildExtraArgs(\SimpleXMLElement $resource): ?array
    {
        if (!isset($resource->extraArgs->arg)) {
            return null;
        }

        $data = [];
        foreach ($resource->extraArgs->arg as $arg) {
            $data[(string) $arg['id']] = $this->buildValues($arg->values);
        }

        return $data;
    }

    private function buildValues(\SimpleXMLElement $resource): array
    {
        $data = [];
        foreach ($resource->value as $value) {
            if (null !== $value->attributes()->name) {
                $data[(string) $value->attributes()->name] = isset($value->values) ? $this->buildValues($value->values) : XmlUtils::phpize($value->__toString());
                continue;
            }

            $data[] = isset($value->values) ? $this->buildValues($value->values) : XmlUtils::phpize($value->__toString());
        }

        return $data;
    }
}
