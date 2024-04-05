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

    public function __construct(private ?array $distinctFormats)
    {
    }

    public function create(string $className, string $format = 'json', ?string $inputOrOutputClass = null, ?Operation $operation = null, array $serializerContext = []): string
    {
        if ($operation) {
            $prefix = $operation->getShortName();
        }

        if (!isset($prefix)) {
            $prefix = (new \ReflectionClass($className))->getShortName();
        }

        if (null !== $inputOrOutputClass && $className !== $inputOrOutputClass) {
            $parts = explode('\\', $inputOrOutputClass);
            $shortName = end($parts);
            $prefix .= '.'.$shortName;
        }

        if ('json' !== $format && ($this->distinctFormats[$format] ?? false)) {
            // JSON is the default, and so isn't included in the definition name
            $prefix .= '.'.$format;
        }

        $definitionName = $serializerContext[SchemaFactory::OPENAPI_DEFINITION_NAME] ?? null;
        if ($definitionName) {
            $name = sprintf('%s-%s', $prefix, $definitionName);
        } else {
            $groups = (array) ($serializerContext[AbstractNormalizer::GROUPS] ?? []);
            $name = $groups ? sprintf('%s-%s', $prefix, implode('_', $groups)) : $prefix;
        }

        return $this->encodeDefinitionName($name);
    }

    private function encodeDefinitionName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9.\-_]/', '.', $name);
    }
}
