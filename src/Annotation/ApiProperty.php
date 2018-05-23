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

/**
 * Property annotation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 * @Attributes(
 *     @Attribute("fetchable", type="bool"),
 *     @Attribute("fetchEager", type="bool"),
 *     @Attribute("jsonldContext", type="array"),
 *     @Attribute("swaggerContext", type="array")
 * )
 */
final class ApiProperty
{
    use AttributesHydratorTrait;

    /**
     * @var string
     */
    public $description;

    /**
     * @var bool
     */
    public $readable;

    /**
     * @var bool
     */
    public $writable;

    /**
     * @var bool
     */
    public $readableLink;

    /**
     * @var bool
     */
    public $writableLink;

    /**
     * @var bool
     */
    public $required;

    /**
     * @var string
     */
    public $iri;

    /**
     * @var bool
     */
    public $identifier;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $fetchable;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var bool
     */
    private $fetchEager;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $jsonldContext;

    /**
     * @see https://github.com/Haehnchen/idea-php-annotation-plugin/issues/112
     *
     * @var array
     */
    private $swaggerContext;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $values = [])
    {
        $this->hydrateAttributes($values);
    }
}
