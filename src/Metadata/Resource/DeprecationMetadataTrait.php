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

namespace ApiPlatform\Metadata\Resource;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * @internal
 */
trait DeprecationMetadataTrait
{
    private $camelCaseToSnakeCaseNameConverter;

    public function getKeyValue(string $key, $value)
    {
        if (!$this->camelCaseToSnakeCaseNameConverter) {
            $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
        }

        if ('attributes' === $key) {
            trigger_deprecation('api-platform/core', '2.7', 'The "attributes" option is deprecated and will be renamed to "extra_properties".');
            $key = 'extra_properties';
        } elseif ('iri' === $key) {
            trigger_deprecation('api-platform/core', '2.7', 'The "iri" is deprecated and will be renamed to "types".');
            $key = 'types';
            $value = [$value];
        } elseif ('validation_groups' === $key) {
            trigger_deprecation('api-platform/core', '2.7', 'The "validation_groups" is deprecated and will be renamed to "validation_context" having an array with a "groups" key.');
            $key = 'validation_context';
            $value = ['groups' => $value];
        } elseif ('access_control' === $key) {
            $key = 'security';
            trigger_deprecation('api-platform/core', '2.7', 'The "access_control" option is deprecated and will be renamed to "security".');
        } elseif ('access_control_message' === $key) {
            $key = 'security_message';
            trigger_deprecation('api-platform/core', '2.7', 'The "access_control_message" option is deprecated and will be renamed to "security_message".');
        } elseif ('path' === $key) {
            $key = 'uri_template';
            trigger_deprecation('api-platform/core', '2.7', 'The "path" option is deprecated and will be renamed to "uri_template".');
        // Transform default value to an empty array if null
        } elseif (\in_array($key, ['denormalization_context', 'normalization_context', 'hydra_context', 'openapi_context', 'order', 'pagination_via_cursor', 'exception_to_status'], true)) {
            if (null === $value) {
                $value = [];
            } elseif (!\is_array($value)) {
                $value = [$value];
            }
        } elseif ('route_prefix' === $key) {
            $value = \is_string($value) ? $value : '';
        } elseif ('swagger_context' === $key) {
            trigger_deprecation('api-platform/core', '2.7', 'The "swagger_context" option is deprecated and will be removed, use "openapi_context".');
            $key = 'openapi_context';
            $value = $value ?? [];
        } elseif ('query_parameter_validation_enabled' === $key) {
            $value = !$value ? false : $value;
        // GraphQl related keys
        } elseif (\in_array($key, ['collection_query', 'item_query', 'mutation'], true)) {
            trigger_deprecation('api-platform/core', '2.7', 'To specify a GraphQl resolver use "resolver" instead of "mutation", "item_query" or "collection_query".');
            $key = 'resolver';
        } elseif ('filters' === $key) {
            $value = null === $value ? [] : $value;
        } elseif ('graphql' === $key) {
            trigger_deprecation('api-platform/core', '2.7', 'The "graphql" option is deprecated and will be renamed to "graphQlOperations".');
            $key = 'graphQlOperations';
        } elseif ('identifiers' === $key) {
            $key = 'uriVariables';
        } elseif ('doctrine_mongodb' === $key) {
            $key = 'extra_properties';
            $value = ['doctrine_mongodb' => $value];
        }

        return [$this->camelCaseToSnakeCaseNameConverter->denormalize($key), $value];
    }
}
