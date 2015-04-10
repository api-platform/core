<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Api\Filter;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Filter implements FilterInterface
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $strategy;

    /**
     * @param string $name
     * @param string $strategy
     */
    public function __construct($name, $strategy = FilterInterface::STRATEGY_EXACT)
    {
        $this->name = $name;
        $this->strategy = $strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getStrategy()
    {
        return $this->strategy;
    }
}
