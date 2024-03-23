<?php

namespace ApiPlatform\Doctrine\Orm\Filter;

/**
 * @author Antoine Bluchet <soyuka@gmail.com>
 *
 * @experimental
 */
interface PropertyAwareFilterInterface extends FilterInterface
{
    /**
     * @param string[] $properties
     */
    public function setProperties(array $properties): void;
}
