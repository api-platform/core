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

use Doctrine\Common\Annotations\Annotation\Attribute;

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
 *     @Attribute("hydraContext", type="array"),
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
 *     @Attribute("swaggerContext", type="array"),
 *     @Attribute("validationGroups", type="mixed")
 * )
 */
final class ApiResource
{
    use AttributesHydratorTrait {
        hydrateAttributes as public __construct;
    }

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
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $accessControl;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $accessControlMessage;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $denormalizationContext;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $fetchPartial;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $forceEager;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string[]
     */
    private $filters;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string[]
     */
    private $hydraContext;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var int
     */
    private $maximumItemsPerPage;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $normalizationContext;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $order;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationClientEnabled;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationClientItemsPerPage;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationClientPartial;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationEnabled;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $paginationFetchJoinCollection;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var int
     */
    private $paginationItemsPerPage;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var int
     */
    private $paginationPartial;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var string
     */
    private $routePrefix;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $swaggerContext;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var mixed
     */
    private $validationGroups;
}
