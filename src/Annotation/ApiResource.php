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
 *     @Attribute("cacheHeaders", type="array"),
 *     @Attribute("denormalizationContext", type="array"),
 *     @Attribute("deprecationReason", type="string"),
 *     @Attribute("elasticsearch", type="bool"),
 *     @Attribute("fetchPartial", type="bool"),
 *     @Attribute("forceEager", type="bool"),
 *     @Attribute("formats", type="array"),
 *     @Attribute("filters", type="string[]"),
 *     @Attribute("hydraContext", type="array"),
 *     @Attribute("input", type="mixed"),
 *     @Attribute("mercure", type="mixed"),
 *     @Attribute("messenger", type="mixed"),
 *     @Attribute("normalizationContext", type="array"),
 *     @Attribute("openapiContext", type="array"),
 *     @Attribute("order", type="array"),
 *     @Attribute("output", type="mixed"),
 *     @Attribute("paginationClientEnabled", type="bool"),
 *     @Attribute("paginationClientItemsPerPage", type="bool"),
 *     @Attribute("paginationClientPartial", type="bool"),
 *     @Attribute("paginationEnabled", type="bool"),
 *     @Attribute("paginationFetchJoinCollection", type="bool"),
 *     @Attribute("paginationItemsPerPage", type="int"),
 *     @Attribute("maximumItemsPerPage", type="int"),
 *     @Attribute("paginationMaximumItemsPerPage", type="int"),
 *     @Attribute("paginationPartial", type="bool"),
 *     @Attribute("paginationViaCursor", type="array"),
 *     @Attribute("routePrefix", type="string"),
 *     @Attribute("security", type="string"),
 *     @Attribute("securityMessage", type="string"),
 *     @Attribute("securityPostDenormalize", type="string"),
 *     @Attribute("securityPostDenormalizeMessage", type="string"),
 *     @Attribute("sunset", type="string"),
 *     @Attribute("swaggerContext", type="array"),
 *     @Attribute("validationGroups", type="mixed")
 * )
 */
final class ApiResource
{
    use AttributesHydratorTrait;

    /**
     * @see https://api-platform.com/docs/core/operations
     *
     * @var array
     */
    public $collectionOperations;

    /**
     * @var string
     */
    public $description;

    /**
     * @see https://api-platform.com/docs/core/graphql
     *
     * @var array
     */
    public $graphql;

    /**
     * @var string
     */
    public $iri;

    /**
     * @see https://api-platform.com/docs/core/operations
     *
     * @var array
     */
    public $itemOperations;

    /**
     * @var string
     */
    public $shortName;

    /**
     * @see https://api-platform.com/docs/core/subresources
     *
     * @var array
     */
    public $subresourceOperations;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $values = [])
    {
        $this->hydrateAttributes($values);
    }
}
