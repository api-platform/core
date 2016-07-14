<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Sanpi\Behatch\Context\RestContext;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class SwaggerContext implements Context
{
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
        $this->restContext = $environment->getContext(RestContext::class);
    }

    /**
     * @Then the Swagger class ":class" exist
     */
    public function assertTheSwaggerClassExist($className)
    {
        $this->getClassInfos($className);
    }

    /**
     * @Then the Swagger class ":class" not exist
     */
    public function assertTheSwaggerClassNotExist($className)
    {
        try {
            $this->getClassInfos($className);

            throw new \PHPUnit_Framework_ExpectationFailedException(sprintf('The class "%s" exist.', $className));
        } catch (\Exception $exception) {
            // an exception must be catched
        }
    }

    /**
     * @Then the Swagger path ":arg1" exist
     */
    public function assertThePathExist($path)
    {
        try {
            \PHPUnit_Framework_Assert::assertTrue($this->assertSwaggerPath($path));

            throw new \PHPUnit_Framework_ExpectationFailedException(sprintf('The path "%s" exist.', $path));
        } catch (\Exception $exception) {
            // an exception must be catched
        }
    }

    /**
     * @Then the value of the node ":node" of the Swagger class ":class" is ":value"
     */
    public function assertNodeValueIs($nodeName, $className, $value)
    {
        $classInfos = $this->getClassInfos($className);
        \PHPUnit_Framework_Assert::assertEquals($this->propertyAccessor->getValue($classInfos, $nodeName), $value);
    }

    /**
     * @Then the value of the node ":node" of the property ":prop" of the Swagger class ":class" is ":value"
     */
    public function assertPropertyNodeValueIs($nodeName, $propertyName, $className, $value)
    {
        $property = $this->getProperty($propertyName, $className);
        if (empty($property)) {
            throw new \PHPUnit_Framework_ExpectationFailedException(
                sprintf('The property "%s" for the class "%s" exist.', $propertyName, $className)
            );
        }
        \PHPUnit_Framework_Assert::assertEquals($this->propertyAccessor->getValue($property, $nodeName), $value);
    }

    /**
     * @Then the value of the node ":node" of the operation ":operation" of the Swagger class ":class" is ":value"
     */
    public function assertOperationNodeValueIs($nodeName, $operationMethod, $className, $value)
    {
        $property = $this->getOperation($operationMethod, $className);

        \PHPUnit_Framework_Assert::assertEquals($this->propertyAccessor->getValue($property, $nodeName), $value);
    }

    /**
     * @Then :nb operations are available for Swagger class ":class"
     */
    public function assertNbOperationsExist($nb, $className)
    {
        $operations = $this->getOperations($className);

        \PHPUnit_Framework_Assert::assertEquals($nb, count($operations));
    }

    /**
     * @Then :nb properties are available for Swagger class ":class"
     */
    public function assertNbPropertiesExist($nb, $className)
    {
        $properties = $this->getProperties($className);

        \PHPUnit_Framework_Assert::assertEquals($nb, count($properties));
    }

    /**
     * @Then ":prop" property doesn't exist for the Swagger class ":class"
     */
    public function assertPropertyNotExist($propertyName, $className)
    {
        $property = $this->getProperty($propertyName, $className);
        if (empty($property)) {
            throw new \PHPUnit_Framework_ExpectationFailedException(
                    sprintf('The property "%s" for the class "%s" exist.', $propertyName, $className)
                );
        }
    }

    /**
     * @Then ":prop" property is required for Swagger class ":class"
     */
    public function assertPropertyIsRequired(string $propertyName, string $className)
    {
        $classInfo = $this->getClassInfos($className);
        if (!in_array($propertyName, $classInfo->{'required'})) {
            throw new \Exception(sprintf('Property "%s" of class "%s" is not required', $propertyName, $className));
        }
    }

    private function getProperty(string $propertyName, string $className) : stdClass
    {
        $properties = $this->getProperties($className);
        $propertyInfos = null;
        foreach ($properties as $classPropertyName => $property) {
            if ($classPropertyName === $propertyName) {
                return $property;
            }
        }

        return new stdClass();
    }

    /**
     * Gets an operation by its method name.
     *
     * @param string $className
     * @param string $method
     *
     * @throws Exception
     *
     * @return array | stdClass
     */
    private function getOperation(string $method, string $className) : stdClass
    {
        foreach ($this->getOperations($className) as $classMethod => $operation) {
            if ($classMethod === $method) {
                return $operation;
            }
        }

        throw new \Exception(sprintf('Operation "%s" of class "%s" does not exist.', $method, $className));
    }

    /**
     * Gets all operations of a given class.
     */
    private function getOperations(string $className) : stdClass
    {
        $classInfos = $this->getClassInfos($className);

        return empty($classInfos) ? $classInfos : new stdClass();
    }

    /**
     * Gets all properties of a given class.
     */
    private function getProperties(string $className) : stdClass
    {
        $classInfos = $this->getClassInfos($className);

        return empty($classInfos->{'properties'}) ? $classInfos->{'properties'} : new stdClass();
    }

    private function assertSwaggerPath(string $expectedPath, bool $getOperation = false): bool
    {
        $json = $this->getLastJsonResponse();
        $validPath = false;
        if (isset($json->{'paths'}) && $getOperation) {
            foreach ($json->{'paths'} as $classTitle => $classPath) {
                if ($expectedPath === $classPath) {
                    return true;
                }
            }
        }

        return $validPath;
    }

    private function getClassInfos(string $className, bool $getOperation = false) : stdClass
    {
        $json = $this->getLastJsonResponse();
        $classInfos = null;

        if (isset($json->{'definitions'}) && !$getOperation) {
            foreach ($json->{'definitions'} as $classTitle => $classData) {
                if ($classTitle === $className) {
                    $classInfos = $classData;
                }
            }
        }

        if (isset($json->{'paths'}) && $getOperation) {
            foreach ($json->{'paths'} as $classTitle => $classPath) {
                foreach ($classPath as $classOperations) {
                    foreach ($classOperations as $classOperation) {
                        if (in_array($className, $classOperation['tags'])) {
                            $classInfos = $classOperations;
                        }
                    }
                }
            }
        }

        if (empty($classInfos)) {
            throw new \Exception(sprintf('Class %s cannot be found in the vocabulary', $className));
        }

        return $classInfos;
    }

    private function getLastJsonResponse() : stdClass
    {
        $content = $this->restContext->getMink()->getSession()->getDriver()->getContent();
        if (null === ($decoded = json_decode($content))) {
            throw new \RuntimeException('JSON response seems to be invalid');
        }

        return $decoded;
    }
}
