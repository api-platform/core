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

    public function getKeyValue($key, $value)
    {
        if (!$this->camelCaseToSnakeCaseNameConverter) {
            $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
        }

        if ('attributes' === $key) {
            @trigger_error('The "attributes" option is deprecated in 2.7 and will be renamed to "extra_properties" in 3.0.', \E_USER_DEPRECATED);
            $key = 'extra_properties';
        } elseif ('iri' === $key) {
            @trigger_error('The "iri" is deprecated in 2.7 and will be renamed to "types" in 3.0.', \E_USER_DEPRECATED);
            $key = 'types';
            $value = [$value];
        } elseif ('validation_groups' === $key) {
            @trigger_error('The "validation_groups" is deprecated in 2.7 and will be renamed to "validation_context" having an array with a "groups" key in 3.0.', \E_USER_DEPRECATED);
            $key = 'validation_context';
            $value = ['groups' => \is_array($value) ? $value : [$value]];
        } elseif ('access_control' === $key) {
            $key = 'security';
            @trigger_error('The "access_control" option is deprecated in 2.7 and will be renamed to "security" in 3.0.', \E_USER_DEPRECATED);
        } elseif ('path' === $key) {
            $key = 'uri_template';
            @trigger_error('The "path" option is deprecated in 2.7 and will be renamed to "uri_tempalte" in 3.0.', \E_USER_DEPRECATED);
        // Transform default value to an empty array if null
        } elseif (\in_array($key, ['denormalization_context', 'normalization_context', 'hydra_context', 'openapi_context', 'order', 'pagination_via_cursor', 'exception_to_status'], true)) {
            $value = \is_array($value) ? $value : [$value];
        } elseif (\in_array($key, ['route_prefix'], true)) {
            $value = \is_string($value) ? $value : '';
        } elseif (\in_array($key, ['swagger_context'], true)) {
            @trigger_error('The "swagger_context" option is deprecated in 2.7 and will be removed in 3.0.', \E_USER_DEPRECATED);

            return [null, null, true];
        } elseif ('query_parameter_validation_enabled' === $key) {
            $value = !$value ? false : $value;
        }

        return [$this->camelCaseToSnakeCaseNameConverter->denormalize($key), $value, false];
    }
}
