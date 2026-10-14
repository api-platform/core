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

namespace ApiPlatform\JsonSchema\Generator;

use ApiPlatform\Metadata\HttpOperation;
use Symfony\Component\JsonSchema\Configuration;
use Symfony\Component\JsonSchema\DefinitionParent;
use Symfony\Component\JsonSchema\DefinitionProcessor\DefinitionProcessorInterface;
use Symfony\Component\JsonSchema\SchemaDocument;

final class NestedOperationDefinitionProcessor implements DefinitionProcessorInterface
{
    public function __construct(private readonly ApiPlatformDefinitionPolicy $definitionPolicy)
    {
    }

    public function process(array $definition, string $class, Configuration $config, SchemaDocument $document, ?DefinitionParent $parent): array
    {
        if (null === $parent || null === $operation = $this->definitionPolicy->findNestedOperation($class)) {
            return $definition;
        }

        if ($description = $operation->getDescription()) {
            $definition['description'] = $description;
        }

        if ($operation instanceof HttpOperation && ($type = $operation->getTypes()[0] ?? null)) {
            $definition['externalDocs'] = ['url' => $type];
        }

        return $definition;
    }
}
