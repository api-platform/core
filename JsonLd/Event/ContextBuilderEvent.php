<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\ApiBundle\JsonLd\Event;

use Dunglas\ApiBundle\Api\ResourceInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * ContextBuilderEvent.
 *
 * @author Luc Vieillescazes <luc@vieillescazes.net>
 */
class ContextBuilderEvent extends Event
{
    /**
     * @var array
     */
    private $context;
    /**
     * @var ResourceInterface
     */
    private $resource;

    /**
     * @param array             $context
     * @param ResourceInterface $resource
     */
    public function __construct(array $context, ResourceInterface $resource = null)
    {
        $this->context = $context;
        $this->resource = $resource;
    }

    /**
     * @return ResourceInterface|null
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param array $context
     *
     * @return array
     */
    public function setContext(array $context)
    {
        $this->context = $context;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return ContextBuilderEvent
     */
    public function addToContext($key, $value)
    {
        $this->context[$key] = $value;

        return $this;
    }
}
