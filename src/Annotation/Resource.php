<?php

/*
 * This file is part of the API Platform Builder package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Builder\Annotation;

/**
 * Resource annotation.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @Annotation
 * @Target({"CLASS"})
 */
final class Resource
{
    /**
     * @var string
     */
    public $shortName;

    /**
     * @var string|null
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
    public $attributes = [];
}
