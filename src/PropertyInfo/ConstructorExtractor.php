<?php
declare(strict_types=1);

namespace ApiPlatform\Core\PropertyInfo;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;

class ConstructorExtractor implements PropertyAccessExtractorInterface
{
    public function isReadable($class, $property, array $context = [])
    {
        return;
    }

    public function isWritable($class, $property, array $context = [])
    {
        $constructor = (new ReflectionClass($class))->getConstructor();

        if(!$constructor) {
            return null; // give a chance for other PropertyExtractors
        }

        $constructorParameters = $constructor->getParameters();
        foreach ($constructorParameters as $constructorParameter) {
            if ($property === $constructorParameter->getName()) {
                return true;
            }
        }

        return null; // give a chance for other PropertyExtractors
    }
}
