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

use Symfony\Component\JsonSchema\Configuration;
use Symfony\Component\TypeInfo\Type;

final readonly class SchemaGenerationRequest
{
    /**
     * @param array<string, mixed> $propertyOptions
     * @param list<string>         $groups
     */
    public function __construct(
        public Type $type,
        public Configuration $configuration,
        public string $schemaType,
        public string $format,
        public bool $partial,
        public string $version,
        public array $propertyOptions,
        public ?string $description = null,
        public bool $deprecated = false,
        public ?string $externalDocs = null,
        public array $groups = [],
    ) {
    }
}
