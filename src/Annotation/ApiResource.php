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

namespace ApiPlatform\Core\Annotation;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Doctrine\Common\Util\Inflector;

/**
 * ApiResource annotation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @Annotation
 * @Target({"CLASS"})
 * @Attributes(
 *     @Attribute("accessControl", type="string"),
 *     @Attribute("accessControlMessage", type="string"),
 *     @Attribute("attributes", type="array"),
 *     @Attribute("collectionOperations", type="array"),
 *     @Attribute("denormalizationContext", type="array"),
 *     @Attribute("description", type="string"),
 *     @Attribute("fetchPartial", type="bool"),
 *     @Attribute("forceEager", type="bool"),
 *     @Attribute("filters", type="string[]"),
 *     @Attribute("graphql", type="array"),
 *     @Attribute("iri", type="string"),
 *     @Attribute("itemOperations", type="array"),
 *     @Attribute("maximumItemsPerPage", type="int"),
 *     @Attribute("normalizationContext", type="array"),
 *     @Attribute("order", type="array"),
 *     @Attribute("paginationClientEnabled", type="bool"),
 *     @Attribute("paginationClientItemsPerPage", type="bool"),
 *     @Attribute("paginationClientPartial", type="bool"),
 *     @Attribute("paginationEnabled", type="bool"),
 *     @Attribute("paginationFetchJoinCollection", type="bool"),
 *     @Attribute("paginationItemsPerPage", type="int"),
 *     @Attribute("paginationPartial", type="bool"),
 *     @Attribute("routePrefix", type="string"),
 *     @Attribute("shortName", type="string"),
 *     @Attribute("subresourceOperations", type="array"),
 *     @Attribute("validationGroups", type="mixed")
 * )
 */
final class ApiResource
{
    const ATTRIBUTES = [
        'accessControl',
        'accessControlMessage',
        'denormalizationContext',
        'fetchPartial',
        'forceEager',
        'filters',
        'maximumItemsPerPage',
        'normalizationContext',
        'order',
        'paginationClientEnabled',
        'paginationClientItemsPerPage',
        'paginationClientPartial',
        'paginationEnabled',
        'paginationFetchJoinCollection',
        'paginationItemsPerPage',
        'paginationPartial',
        'routePrefix',
        'validationGroups',
    ];

    /**
     * @var string
     */
    public $shortName;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $iri;

    /**
     * @var array
     */
    public $itemOperations;

    /**
     * @var array
     */
    public $collectionOperations;

    /**
     * @var array
     */
    public $subresourceOperations;

    /**
     * @var array
     */
    public $graphql;

    /**
     * @var array
     */
    public $attributes = [];

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $values = [])
    {
        if (isset($values['attributes'])) {
            $this->attributes = $values['attributes'];
            unset($values['attributes']);
        }

        foreach ($values as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            } elseif (\in_array($key, self::ATTRIBUTES, true)) {
                $this->attributes += [Inflector::tableize($key) => $value];
            } else {
                throw new InvalidArgumentException(sprintf('Unknown property "%s" on annotation "%s".', $key, self::class));
            }
        }
    }
}
