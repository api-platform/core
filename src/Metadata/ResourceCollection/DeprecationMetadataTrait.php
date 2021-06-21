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

namespace ApiPlatform\Core\Metadata\ResourceCollection;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * @internal
 */
trait DeprecationMetadataTrait
{
    private $converter;

    public function getKeyValue($key, $value) {
        if (!$this->converter) {
            $this->converter = new CamelCaseToSnakeCaseNameConverter();
        }

        if ($key === 'attributes') {
            @trigger_error('The "attributes" option is deprecated in 2.7 and will be renamed to "extra_properties" in 3.0.', E_USER_DEPRECATED);
            $key = 'extra_properties';
        } else if ($key === 'iri') {
            @trigger_error('The "iri" is deprecated in 2.7 and will be renamed to "types" in 3.0.', E_USER_DEPRECATED);
            $key = 'types';
            $value = [$value];
        } else if ($key === 'validation_groups') {
            @trigger_error('The "validation_groups" is deprecated in 2.7 and will be renamed to "validation_context" having an array with a "groups" key in 3.0.', E_USER_DEPRECATED);
            $key = 'validation_context';
            $value = ['groups' => is_array($value) ? $value : [$value]];
        } else if ($key === 'access_control') {
            $key = 'security';
            @trigger_error('The "access_control" option is deprecated in 2.7 and will be renamed to "security" in 3.0.', E_USER_DEPRECATED);
        } else if ($key === 'path') {
            $key = 'uri_template';
            @trigger_error('The "path" option is deprecated in 2.7 and will be renamed to "uri_tempalte" in 3.0.', E_USER_DEPRECATED);
        }

        return [$this->converter->denormalize($key), $value];
    }
}
