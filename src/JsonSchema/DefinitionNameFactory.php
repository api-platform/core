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

namespace ApiPlatform\JsonSchema;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\ResourceClassInfoTrait;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class DefinitionNameFactory implements DefinitionNameFactoryInterface
{
    use ResourceClassInfoTrait;

    private const GLUE = '.';
    private array $prefixCache = [];

    public function __construct(private ?array $distinctFormats = null)
    {
        if ($distinctFormats) {
            trigger_deprecation('api-platform/json-schema', '4.1', 'The distinctFormats argument is deprecated and will be removed in 5.0.');
        }
    }

    public function create(string $className, string $format = 'json', ?string $inputOrOutputClass = null, ?Operation $operation = null, array $serializerContext = []): string
    {
        if ($operation) {
            $prefix = $operation->getShortName();
        }

        if (!isset($prefix)) {
            $prefix = $this->createPrefixFromClass($className);
        }

        if (null !== $inputOrOutputClass && $className !== $inputOrOutputClass) {
            $parts = explode('\\', $inputOrOutputClass);
            $shortName = end($parts);
            $prefix .= self::GLUE.$shortName;
        }

        // TODO: remove in 5.0
        $v = $this->distinctFormats ? ($this->distinctFormats[$format] ?? false) : true;

        if ('json' !== $format && $v) {
            // JSON is the default, and so isn't included in the definition name
            $prefix .= self::GLUE.$format;
        }

        $definitionName = $serializerContext[SchemaFactory::OPENAPI_DEFINITION_NAME] ?? null;
        if (null !== $definitionName) {
            $name = \sprintf('%s%s', $prefix, $definitionName ? '-'.$definitionName : $definitionName);
        } else {
            $groups = (array) ($serializerContext[AbstractNormalizer::GROUPS] ?? []);
            $name = $groups ? \sprintf('%s-%s', $prefix, implode('_', $groups)) : $prefix;
        }

        return $this->encodeDefinitionName($name);
    }

    private function encodeDefinitionName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9.\-_]/', '.', $name);
    }

    private function createPrefixFromClass(string $fullyQualifiedClassName, int $namespaceParts = 1): string
    {
        $parts = explode('\\', $fullyQualifiedClassName);
        $name = implode(self::GLUE, \array_slice($parts, -$namespaceParts));

        if (!isset($this->prefixCache[$name])) {
            $this->prefixCache[$name] = $fullyQualifiedClassName;

            return $name;
        }

        if ($this->prefixCache[$name] !== $fullyQualifiedClassName) {
            $name = $this->createPrefixFromClass($fullyQualifiedClassName, ++$namespaceParts);
        }

        return $name;
    }
}
