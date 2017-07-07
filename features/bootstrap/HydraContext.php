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

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Symfony\Component\PropertyAccess\PropertyAccess;

class HydraContext implements Context
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Gives access to the Behatch context.
     *
     * @param BeforeScenarioScope $scope
     *
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        /** @var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext('Behatch\Context\RestContext');
    }

    /**
     * @Then the Hydra class ":class" exist
     */
    public function assertTheHydraClassExist($className)
    {
        $this->getClassInfos($className);
    }

    /**
     * @Then the Hydra class ":class" not exist
     */
    public function assertTheHydraClassNotExist($className)
    {
        try {
            $this->getClassInfos($className);

            throw new \PHPUnit_Framework_ExpectationFailedException(sprintf('The class "%s" exist.', $className));
        } catch (\Exception $exception) {
            // an exception must be catched
        }
    }

    /**
     * @Then the value of the node ":node" of the Hydra class ":class" is ":value"
     */
    public function assertNodeValueIs($nodeName, $className, $value)
    {
        $classInfos = $this->getClassInfos($className);

        \PHPUnit_Framework_Assert::assertEquals($this->propertyAccessor->getValue($classInfos, $nodeName), $value);
    }

    /**
     * @Then the value of the node ":node" of the property ":prop" of the Hydra class ":class" is ":value"
     */
    public function assertPropertyNodeValueIs($nodeName, $propertyName, $className, $value)
    {
        $property = $this->getProperty($propertyName, $className);

        \PHPUnit_Framework_Assert::assertEquals($this->propertyAccessor->getValue($property, $nodeName), $value);
    }

    /**
     * @Then the value of the node ":node" of the operation ":operation" of the Hydra class ":class" is ":value"
     */
    public function assertOperationNodeValueIs($nodeName, $operationMethod, $className, $value)
    {
        $property = $this->getOperation($operationMethod, $className);

        \PHPUnit_Framework_Assert::assertEquals($this->propertyAccessor->getValue($property, $nodeName), $value);
    }

    /**
     * @Then :nb operations are available for Hydra class ":class"
     */
    public function assertNbOperationsExist($nb, $className)
    {
        $operations = $this->getOperations($className);

        \PHPUnit_Framework_Assert::assertEquals($nb, count($operations));
    }

    /**
     * @Then :nb properties are available for Hydra class ":class"
     */
    public function assertNbPropertiesExist($nb, $className)
    {
        $properties = $this->getProperties($className);

        \PHPUnit_Framework_Assert::assertEquals($nb, count($properties));
    }

    /**
     * @Then ":prop" property doesn't exist for the Hydra class ":class"
     */
    public function assertPropertyNotExist($propertyName, $className)
    {
        try {
            $this->getProperty($propertyName, $className);

            throw new \PHPUnit_Framework_ExpectationFailedException(
                sprintf('The property "%s" for the class "%s" exist.', $propertyName, $className)
            );
        } catch (\Exception $exception) {
            // an exception must be catched
        }
    }

    /**
     * @Then ":prop" property is readable for Hydra class ":class"
     */
    public function assertPropertyIsReadable($propertyName, $className)
    {
        $properties = $this->getProperty($propertyName, $className);

        if (empty($properties->{'hydra:readable'})) {
            throw new \Exception(sprintf('Property "%s" of class "%s" is not readable', $propertyName, $className));
        }
    }

    /**
     * @Then ":prop" property is not readable for Hydra class ":class"
     */
    public function assertPropertyIsNotReadable($propertyName, $className)
    {
        $properties = $this->getProperty($propertyName, $className);

        if (!empty($properties->{'hydra:readable'})) {
            throw new \Exception(sprintf('Property "%s" of class "%s" is readable', $propertyName, $className));
        }
    }

    /**
     * @Then ":prop" property is writable for Hydra class ":class"
     */
    public function assertPropertyIsWritable($propertyName, $className)
    {
        $properties = $this->getProperty($propertyName, $className);

        if (empty($properties->{'hydra:writable'})) {
            throw new \Exception(sprintf('Property "%s" of class "%s" is not writable', $propertyName, $className));
        }
    }

    /**
     * @Then ":prop" property is required for Hydra class ":class"
     */
    public function assertPropertyIsRequired($propertyName, $className)
    {
        $properties = $this->getProperty($propertyName, $className);

        if (empty($properties->{'hydra:required'})) {
            throw new \Exception(sprintf('Property "%s" of class "%s" is not required', $propertyName, $className));
        }
    }

    /**
     * @param string $propertyName
     * @param string $className
     *
     * @throws Exception
     *
     * @return array
     */
    private function getProperty($propertyName, $className)
    {
        $properties = $this->getProperties($className);
        $propertyInfos = null;
        foreach ($properties as $property) {
            if ($property->{'hydra:title'} === $propertyName) {
                $propertyInfos = $property;
            }
        }

        if (empty($propertyInfos)) {
            throw new \Exception(sprintf('Property "%s" of class "%s" does\'nt exist', $propertyName, $className));
        }

        return $propertyInfos;
    }

    /**
     * Gets an operation by its method name.
     *
     * @param string $className
     * @param string $method
     *
     * @throws Exception
     *
     * @return array
     */
    private function getOperation($method, $className)
    {
        foreach ($this->getOperations($className) as $operation) {
            if ($operation->{'hydra:method'} === $method) {
                return $operation;
            }
        }

        throw new \Exception(sprintf('Operation "%s" of class "%s" doesn\'t exist.', $method, $className));
    }

    /**
     * Gets all operations of a given class.
     *
     * @param string $className
     *
     * @throws Exception
     *
     * @return array
     */
    private function getOperations($className)
    {
        $classInfos = $this->getClassInfos($className);

        return $classInfos->{'hydra:supportedOperation'} ?? [];
    }

    /**
     * Gets all properties of a given class.
     *
     * @param string $className
     *
     * @throws Exception
     *
     * @return array
     */
    private function getProperties($className)
    {
        $classInfos = $this->getClassInfos($className);

        return $classInfos->{'hydra:supportedProperty'} ?? [];
    }

    /**
     * Gets information about a class.
     *
     * @param string $className
     *
     * @throws Exception
     *
     * @return array
     */
    private function getClassInfos($className)
    {
        $json = $this->getLastJsonResponse();
        $classInfos = null;

        if (isset($json->{'hydra:supportedClass'})) {
            foreach ($json->{'hydra:supportedClass'} as $classData) {
                if ($classData->{'hydra:title'} === $className) {
                    $classInfos = $classData;
                }
            }
        }

        if (empty($classInfos)) {
            throw new \Exception(sprintf('Class %s cannot be found in the vocabulary', $className));
        }

        return $classInfos;
    }

    /**
     * Gets the last JSON response.
     *
     * @return array
     */
    private function getLastJsonResponse()
    {
        $content = $this->restContext->getMink()->getSession()->getDriver()->getContent();
        if (null === ($decoded = json_decode($content))) {
            throw new \RuntimeException('JSON response seems to be invalid');
        }

        return $decoded;
    }
}
