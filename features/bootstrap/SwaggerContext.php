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
use Behatch\Context\RestContext;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

final class SwaggerContext implements Context
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
        /** @var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext(RestContext::class);
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
    public function assertTheOPenAPIClassNotExist(string $className)
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
     * @Then :prop property exists for the Swagger class :class
     */
    public function assertPropertyExistForTheSwaggerClass(string $propertyName, string $className)
    {
        try {
            $this->getPropertyInfo($propertyName, $className);
        } catch (\InvalidArgumentException $e) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" exists.', $propertyName, $className), null, $e);
        }
    }

    /**
     * @Then :prop property exists for the OpenAPI class :class
     */
    public function assertPropertyExistForTheOpenApiClass(string $propertyName, string $className)
    {
        try {
            $this->getPropertyInfo($propertyName, $className, 3);
        } catch (\InvalidArgumentException $e) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" exists.', $propertyName, $className), null, $e);
        }
    }

    /**
     * @Then :prop property is required for Swagger class :class
     */
    public function assertPropertyIsRequiredForSwagger(string $propertyName, string $className)
    {
        if (!in_array($propertyName, $this->getClassInfo($className)->required, true)) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" should be required', $propertyName, $className));
        }
    }

    /**
     * @Then :prop property is required for OpenAPi class :class
     */
    public function assertPropertyIsRequiredForOpenAPi(string $propertyName, string $className)
    {
        if (!in_array($propertyName, $this->getClassInfo($className, 3)->required, true)) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" should be required', $propertyName, $className));
        }
    }

    /**
     * Gets information about a property.
     *
     * @throws \InvalidArgumentException
     */
    private function getPropertyInfo(string $propertyName, string $className, int $specVersion = 2): stdClass
    {
        foreach ($this->getProperties($className, $specVersion) as $classPropertyName => $property) {
            if ($classPropertyName === $propertyName) {
                return $property;
            }
        }

        throw new \InvalidArgumentException(sprintf('The property "%s" for the class "%s" doesn\'t exist.', $propertyName, $className));
    }

    /**
     * Gets all operations of a given class.
     */
    private function getProperties(string $className, int $specVersion = 2): stdClass
    {
        return $this->getClassInfo($className, $specVersion)->{'properties'} ?? new \stdClass();
    }

    /**
     * Gets information about a class.
     *
     * @throws \InvalidArgumentException
     */
    private function getClassInfo(string $className, int $specVersion = 2): stdClass
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
    private function getLastJsonResponse(): stdClass
    {
        if (null === ($decoded = json_decode($this->restContext->getMink()->getSession()->getDriver()->getContent()))) {
            throw new \RuntimeException('JSON response seems to be invalid');
        }

        return $decoded;
    }
}
