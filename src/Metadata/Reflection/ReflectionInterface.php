<?php

namespace ApiPlatform\Core\Metadata\Reflection;

/**
 * @author Théo FIDRY <theo.fidry@gmail.com>
 */
interface ReflectionInterface
{
    /**
     * @return string[]
     *
     * @example
     *  ['get', 'is', ...]
     */
    public function getAccessorPrefixes();

    /**
     * @return string[]
     *
     * @example
     *  ['set', 'add', ...]
     */
    public function getMutatorPrefixes();

    /**
     * @return string[] Return all the prefixes.
     */
    public function getPrefixes();

    /**
     * Gets the property name associated with an accessor method.
     *
     * @param string $methodName
     *
     * @return string|null Property name associated with an accessor method.
     *                     
     * @example
     *  getProperty('setName') => 'name' (checks are case insensitive) 
     */
    public function getProperty($methodName);
}
