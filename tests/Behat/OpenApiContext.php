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

namespace ApiPlatform\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behatch\Context\RestContext;
use Behatch\Json\Json;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;

final class OpenApiContext implements Context
{
    private ?RestContext $restContext = null;

    /**
     * Gives access to the Behatch context.
     *
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
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
     * @Then the OpenAPI class :class exists
     */
    public function assertTheOpenApiClassExist(string $className): void
    {
        try {
            $this->getClassInfo($className);
        } catch (\InvalidArgumentException $e) {
            throw new ExpectationFailedException(sprintf('The class "%s" doesn\'t exist.', $className), null, $e);
        }
    }

    /**
     * @Then the OpenAPI class :class doesn't exist
     */
    public function assertTheOpenAPIClassNotExist(string $className): void
    {
        try {
            $this->getClassInfo($className);
        } catch (\InvalidArgumentException) {
            return;
        }

        throw new ExpectationFailedException(sprintf('The class "%s" exists.', $className));
    }

    /**
     * @Then the OpenAPI path :arg1 exists
     */
    public function assertThePathExist(string $path): void
    {
        $json = $this->getLastJsonResponse();

        Assert::assertTrue(isset($json->paths) && isset($json->paths->{$path}));
    }

    /**
     * @Then the :prop property exists for the OpenAPI class :class
     */
    public function assertThePropertyExistForTheOpenApiClass(string $propertyName, string $className): void
    {
        try {
            $this->getPropertyInfo($propertyName, $className);
        } catch (\InvalidArgumentException $e) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" doesn\'t exist.', $propertyName, $className), null, $e);
        }
    }

    /**
     * @Then the :prop property is required for the OpenAPI class :class
     */
    public function assertThePropertyIsRequiredForTheOpenAPIClass(string $propertyName, string $className): void
    {
        if (!\in_array($propertyName, $this->getClassInfo($className)->required, true)) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" should be required', $propertyName, $className));
        }
    }

    /**
     * @Then the :prop property is not read only for the OpenAPI class :class
     */
    public function assertThePropertyIsNotReadOnlyForTheOpenAPIClass(string $propertyName, string $className): void
    {
        $propertyInfo = $this->getPropertyInfo($propertyName, $className);
        if (property_exists($propertyInfo, 'readOnly') && $propertyInfo->readOnly) {
            throw new ExpectationFailedException(sprintf('Property "%s" of class "%s" should not be read only', $propertyName, $className));
        }
    }

    /**
     * @Then the :prop property for the OpenAPI class :class should be equal to:
     */
    public function assertThePropertyForTheOpenAPIClassShouldBeEqualTo(string $propertyName, string $className, PyStringNode $propertyContent): void
    {
        $propertyInfo = $this->getPropertyInfo($propertyName, $className);
        $propertyInfoJson = new Json(json_encode($propertyInfo));

        if (new Json($propertyContent) != $propertyInfoJson) {
            throw new ExpectationFailedException(sprintf("Property \"%s\" of class \"%s\" is '%s'", $propertyName, $className, $propertyInfoJson));
        }
    }

    /**
     * Gets information about a property.
     *
     * @throws \InvalidArgumentException
     */
    private function getPropertyInfo(string $propertyName, string $className): \stdClass
    {
        /** @var iterable $properties */
        $properties = $this->getProperties($className);
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
    private function getProperties(string $className): \stdClass
    {
        return $this->getClassInfo($className)->{'properties'} ?? new \stdClass();
    }

    /**
     * Gets information about a class.
     *
     * @throws \InvalidArgumentException
     */
    private function getClassInfo(string $className): \stdClass
    {
        $nodes = $this->getLastJsonResponse()->{'components'}->{'schemas'};
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
        if (null === ($decoded = json_decode($this->restContext->getMink()->getSession()->getDriver()->getContent(), null, 512, \JSON_THROW_ON_ERROR))) {
            throw new \RuntimeException('JSON response seems to be invalid');
        }

        return $decoded;
    }
}
