<?php

/*
 * This file is part of the DunglasApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

class HydraContext implements Context
{
    /**
     * @param BeforeScenarioScope $scope
     *
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        /** @var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();
        $this->restContext = $environment->getContext('Sanpi\Behatch\Context\RestContext');
    }

    /**
     * @Then the hydra class ":class" exist
     */
    public function assertTheHydraClassExist($className)
    {
        $this->getClassInfos($className);
    }

    /**
     * @Then :nb operations are available for hydra class ":class"
     */
    public function assertNbOperationsExist($nb, $className)
    {
        $operations = $this->getOperations($className);

        \PHPUnit_Framework_Assert::assertEquals(
            $nb,
            count($operations)
        );
    }

    /**
     * @Then :nb properties are available for hydra class ":class"
     */
    public function assertNbPropertiesExist($nb, $className)
    {
        $properties = $this->getProperties($className);

        \PHPUnit_Framework_Assert::assertEquals(
            $nb,
            count($properties)
        );
    }

    /**
     * @Then ":prop" property is readable for hydra class ":class"
     */
    public function assertPropertyIsReadable($propertyName, $className)
    {
        $propertyInfos = $this->getProperty($propertyName, $className);

        if (empty($propertyInfos['hydra:readable'])) {
            throw new Exception(sprintf('Property "%s" of class "%s" is not readable', $propertyName, $className));
        }
    }

    /**
     * @Then ":prop" property is not readable for hydra class ":class"
     */
    public function assertPropertyIsNotReadable($propertyName, $className)
    {
        $propertyInfos = $this->getProperty($propertyName, $className);

        if (!empty($propertyInfos['hydra:readable'])) {
            throw new Exception(sprintf('Property "%s" of class "%s" is readable', $propertyName, $className));
        }
    }

    /**
     * @Then ":prop" property is writable for hydra class ":class"
     */
    public function assertPropertyIsWritable($propertyName, $className)
    {
        $propertyInfos = $this->getProperty($propertyName, $className);

        if (empty($propertyInfos['hydra:writable'])) {
            throw new Exception(sprintf('Property "%s" of class "%s" is not writable', $propertyName, $className));
        }
    }

    /**
     * @Then ":prop" property is required for hydra class ":class"
     */
    public function assertPropertyIsRequired($propertyName, $className)
    {
        $propertyInfos = $this->getProperty($propertyName, $className);

        if (empty($propertyInfos['hydra:required'])) {
            throw new Exception(sprintf('Property "%s" of class "%s" is not required', $propertyName, $className));
        }
    }

    /**
     * @param string $propertyName
     * @param string $className
     *
     * @return array
     *
     * @throws Exception
     */
    private function getProperty($propertyName, $className)
    {
        $properties = $this->getProperties($className);
        $propertyInfos = null;
        foreach ($properties as $property) {
            if ($property['hydra:title'] == $propertyName) {
                $propertyInfos = $property;
            }
        }

        if (empty($propertyInfos)) {
            throw new Exception(sprintf('Property "%s" of class "%s" not exist', $propertyName, $className));
        }

        return $propertyInfos;
    }

    /**
     * @param string $className
     *
     * @return array
     *
     * @throws Exception
     */
    private function getOperations($className)
    {
        $classInfos = $this->getClassInfos($className);

        return isset($classInfos['hydra:supportedOperation']) ? $classInfos['hydra:supportedOperation'] : [];
    }

    /**
     * @param string $className
     *
     * @return array
     *
     * @throws Exception
     */
    private function getProperties($className)
    {
        $classInfos = $this->getClassInfos($className);

        return isset($classInfos['hydra:supportedProperty']) ? $classInfos['hydra:supportedProperty'] : [];
    }

    /**
     * @param string $className
     *
     * @return array
     *
     * @throws Exception
     */
    private function getClassInfos($className)
    {
        $json = $this->getLastJsonResponse();
        $classInfos = null;

        if (isset($json['hydra:supportedClass'])) {
            foreach ($json['hydra:supportedClass'] as $classData) {
                if ($classData['hydra:title'] == $className) {
                    $classInfos = $classData;
                }
            }
        }

        if (empty($classInfos)) {
            throw new Exception(sprintf('Class %s cannot be found in the vocabulary', $className));
        }

        return $classInfos;
    }

    /**
     * @return array
     */
    private function getLastJsonResponse()
    {
        $content = $this->restContext->getSession()->getDriver()->getContent();
        if (null === ($decoded = json_decode($content, true))) {
            throw new \RuntimeException('JSON response seems to be invalid');
        }

        return $decoded;
    }
}
