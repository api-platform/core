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

namespace ApiPlatform\Symfony\Tests\Fixtures\JsonSchemaProcessor;

use ApiPlatform\Metadata\AsJsonSchemaDefinitionProcessor;
use Symfony\Component\JsonSchema\Configuration;
use Symfony\Component\JsonSchema\DefinitionParent;
use Symfony\Component\JsonSchema\DefinitionProcessor\DefinitionProcessorInterface;
use Symfony\Component\JsonSchema\SchemaDocument;

#[AsJsonSchemaDefinitionProcessor]
final class OptedInDefinitionProcessor implements DefinitionProcessorInterface
{
    public function process(array $definition, string $class, Configuration $config, SchemaDocument $document, ?DefinitionParent $parent): array
    {
        return $definition;
    }
}
