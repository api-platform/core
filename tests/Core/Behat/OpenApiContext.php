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

namespace ApiPlatform\Core\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behatch\Context\RestContext;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

final class OpenApiContext implements Context
{
    /**
     * @var RestContext
     */
    private $restContext;

    /**
     * Gives access to the Behatch context.
     *
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        /**
         * @var InitializedContextEnvironment $environment
         */
        $environment = $scope->getEnvironment();
        /**
         * @var RestContext $restContext
         */
        $restContext = $environment->getContext(RestContext::class);
        $this->restContext = $restContext;
    }

    /**
     * @Then the Swagger class :class exists
     */
    public function assertTheSwaggerClassExist(string $className)
    {
        try {
            $this->getClassInfo($className);
        } catch (\InvalidArgumentException $e) {
            throw new ExpectationFailedException(sprintf('The class "%s" doesn\'t exist.', $className), null, $e);
        }
    }

    /**
     * @Then the OpenAPI class :class exists
     */
    public function assertTheOpenApiClassExist(string $className)
    {
        try {
            $this->getClassInfo($className, 3);
        } catch (\InvalidArgumentException $e) {
            throw new ExpectationFailedException(sprintf('The class "%s" doesn\'t exist.', $className), null, $e);
        }
    }

    /**
     * @Then the Swagger class :class doesn't exist
     */
    public function assertTheSwaggerClassNotExist(string $className)
    {
        try {
            $this->getClassInfo($className);
        } catch (\InvalidArgumentException $e) {
            return;
        }

        throw new ExpectationFailedException(sprintf('The class "%s" exists.', $className));
    }

    /**
     * @Then the OpenAPI class :class doesn't exist
     */
    public function assertTheOpenAPIClassNotExist(string $className)
    {
        try {
            $this->getClassInfo($className, 3);
        } catch (\InvalidArgumentException $e) {
            return;
        }

        throw new ExpectationFailedException(sprintf('The class "%s" exists.', $className));
    }

    /**
     * @Then the Swagger path :arg1 exists
     * @Then the OpenAPI path :arg1 exists
     */
    public function assertThePathExist(string $path)
    {
        $json = $this->getLastJsonResponse();

        Assert::assertTrue(isset($json->paths) && isset($json->paths->{$path}));
    }

    /**
     * @Then the :prop property exists for the Swagger class :class
     */
    public function assertThePropertyExistForTheSwaggerClass(string $propertyName, string $className)
    {
        try {
            $this->getPropertyInfo($propertyName, $className);
        } catch (\InvalidArgumentException $e) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" doesn\'t exist.', $propertyName, $className), null, $e);
        }
    }

    /**
     * @Then the :prop property exists for the OpenAPI class :class
     */
    public function assertThePropertyExistForTheOpenApiClass(string $propertyName, string $className)
    {
        try {
            $this->getPropertyInfo($propertyName, $className, 3);
        } catch (\InvalidArgumentException $e) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" doesn\'t exist.', $propertyName, $className), null, $e);
        }
    }

    /**
     * @Then the :prop property is required for the Swagger class :class
     */
    public function assertThePropertyIsRequiredForTheSwaggerClass(string $propertyName, string $className)
    {
        if (!\in_array($propertyName, $this->getClassInfo($className)->required, true)) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" should be required', $propertyName, $className));
        }
    }

    /**
     * @Then the :prop property is required for the OpenAPI class :class
     */
    public function assertThePropertyIsRequiredForTheOpenAPIClass(string $propertyName, string $className)
    {
        if (!\in_array($propertyName, $this->getClassInfo($className, 3)->required, true)) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" should be required', $propertyName, $className));
        }
    }

    /**
     * @Then the :prop property is not read only for the Swagger class :class
     */
    public function assertThePropertyIsNotReadOnlyForTheSwaggerClass(string $propertyName, string $className)
    {
        $propertyInfo = $this->getPropertyInfo($propertyName, $className);
        if (property_exists($propertyInfo, 'readOnly') && $propertyInfo->readOnly) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" should not be read only', $propertyName, $className));
        }
    }

    /**
     * @Then the :prop property is not read only for the OpenAPI class :class
     */
    public function assertThePropertyIsNotReadOnlyForTheOpenAPIClass(string $propertyName, string $className)
    {
        $propertyInfo = $this->getPropertyInfo($propertyName, $className, 3);
        if (property_exists($propertyInfo, 'readOnly') && $propertyInfo->readOnly) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" should not be read only', $propertyName, $className));
        }
    }

    /**
     * Gets information about a property.
     *
     * @throws \InvalidArgumentException
     */
    private function getPropertyInfo(string $propertyName, string $className, int $specVersion = 2): \stdClass
    {
        /**
         * @var iterable $properties
         */
        $properties = $this->getProperties($className, $specVersion);
        foreach ($properties as $classPropertyName => $property) {
            if ($classPropertyName === $propertyName) {
                return $property;
            }
        }

        throw new \InvalidArgumentException(sprintf('The property "%s" for the class "%s" doesn\'t exist.', $propertyName, $className));
    }

    /**
     * Gets all operations of a given class.
     */
    private function getProperties(string $className, int $specVersion = 2): \stdClass
    {
        return $this->getClassInfo($className, $specVersion)->{'properties'} ?? new \stdClass();
    }

    /**
     * Gets information about a class.
     *
     * @throws \InvalidArgumentException
     */
    private function getClassInfo(string $className, int $specVersion = 2): \stdClass
    {
        $nodes = 2 === $specVersion ? $this->getLastJsonResponse()->{'definitions'} : $this->getLastJsonResponse()->{'components'}->{'schemas'};
        foreach ($nodes as $classTitle => $classData) {
            if ($classTitle === $className) {
                return $classData;
            }
        }

        throw new \InvalidArgumentException(sprintf('Class %s cannot be found in the vocabulary', $className));
    }

    /**
     * Gets the last JSON response.
     *
     * @throws \RuntimeException
     */
    private function getLastJsonResponse(): \stdClass
    {
        if (null === ($decoded = json_decode($this->restContext->getMink()->getSession()->getDriver()->getContent()))) {
            throw new \RuntimeException('JSON response seems to be invalid');
        }

        return $decoded;
    }
}
