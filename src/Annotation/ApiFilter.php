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

use ApiPlatform\Core\Api\FilterInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;

/**
 * Filter annotation.
 *
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @Annotation
 * @Target({"PROPERTY", "CLASS"})
 */
final class ApiFilter
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $strategy;

    /**
     * @var string|FilterInterface
     */
    public $filterClass;

    /**
     * @var array
     */
    public $properties = [];

    /**
     * @var array raw arguments for the filter
     */
    public $arguments = [];

    public function __construct($options = [])
    {
        if (!\is_string($options['value'] ?? null)) {
            throw new InvalidArgumentException('This annotation needs a value representing the filter class.');
        }

        if (!is_a($options['value'], FilterInterface::class, true)) {
            throw new InvalidArgumentException(sprintf('The filter class "%s" does not implement "%s". Did you forget a use statement?', $options['value'], FilterInterface::class));
        }

        $this->filterClass = $options['value'];
        unset($options['value']);

        foreach ($options as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new InvalidArgumentException(sprintf('Property "%s" does not exist on the ApiFilter annotation.', $key));
            }

            $this->{$key} = $value;
        }
    }
}
