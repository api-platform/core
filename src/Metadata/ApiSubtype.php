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

namespace ApiPlatform\Metadata;

/**
 * Describes a subtype of a discriminator map that is not a resource.
 *
 * The short name defaults to the class short name. It names the subtype in the JSON Schema and OpenAPI definitions,
 * and scopes its properties in JSON-LD and Hydra. The types default to the short name, they are the JSON-LD "@type"
 * of the subtype and the id of its Hydra class, like the types of a resource.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ApiSubtype
{
    /**
     * @param list<string>|null $types
     */
    public function __construct(public readonly ?string $shortName = null, public readonly ?array $types = null)
    {
    }
}
