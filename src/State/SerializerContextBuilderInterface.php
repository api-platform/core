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

namespace ApiPlatform\State;

use ApiPlatform\Metadata\Exception\RuntimeException;
use ApiPlatform\Metadata\HttpOperation;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds the context used by the Symfony Serializer.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface SerializerContextBuilderInterface
{
    /**
     * Creates a serialization context from a Request.
     *
     * @throws RuntimeException
     *
     * @return array<string, mixed>&array{
     *   groups?: string[]|string,
     *   operation_name?: string,
     *   operation?: HttpOperation,
     *   resource_class?: class-string,
     *   skip_null_values?: bool,
     *   iri_only?: bool,
     *   request_uri?: string,
     *   uri?: string,
     *   input?: array{class: class-string|null},
     *   output?: array{class: class-string|null},
     *   item_uri_template?: string,
     *   types?: string[],
     *   uri_variables?: array<string, string>,
     *   force_resource_class?: class-string,
     *   api_allow_update?: bool,
     *   deep_object_to_populate?: bool,
     *   collect_denormalization_errors?: bool,
     *   exclude_from_cache_key?: string[],
     *   api_included?: bool
     * }
     */
    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array;
}
