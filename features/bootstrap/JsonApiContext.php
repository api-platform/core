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
use Behatch\Context\RestContext;
use Behatch\Json\JsonInspector;
use Behatch\Json\Json;

final class JsonApiContext implements Context
{
    protected $restContext;

    protected $inspector;

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

        $this->inspector = new JsonInspector('javascript');
    }

    /**
     * @Then I save the response
     */
    public function iSaveTheResponse()
    {
        $content = $this->getContent();

        if (null === ($decoded = json_decode($content))) {
            throw new \RuntimeException('JSON response seems to be invalid');
        }

        file_put_contents(dirname(__FILE__) . '/response.json', $content);
    }

    /**
     * @Then I valide it with jsonapi-validator
     */
    public function iValideItWithJsonapiValidator()
    {
        $validationResponse = exec(sprintf('cd %s && jsonapi-validator -f response.json', dirname(__FILE__)));

        $isValidJsonapi = 'response.json is valid JSON API.' === $validationResponse;

        if (!$isValidJsonapi) {
            throw new \RuntimeException('JSON response seems to be invalid JSON API');
        }
    }

    /**
     * Checks that given JSON node is equal to an empty array
     *
     * @Then the JSON node :node should be an empty array
     */
    public function theJsonNodeShouldBeAnEmptyArray($node)
    {
        $actual = $this->getValueOfNode($node);

        if (!is_array($actual) || !empty($actual)) {
            throw new \Exception(
                sprintf("The node value is '%s'", json_encode($actual))
            );
        }
    }

    /**
     * Checks that given JSON node is a number
     *
     * @Then the JSON node :node should be a number
     */
    public function theJsonNodeShouldBeANumber($node)
    {
        $actual = $this->getValueOfNode($node);

        if (!is_numeric($actual)) {
            throw new \Exception(
                sprintf('The node value is `%s`', json_encode($actual))
            );
        }
    }

    /**
     * Checks that given JSON node is not an empty string
     *
     * @Then the JSON node :node should not be an empty string
     */
    public function theJsonNodeShouldNotBeAnEmptyString($node)
    {
        $actual = $this->getValueOfNode($node);

        if ($actual === '') {
            throw new \Exception(
                sprintf('The node value is `%s`', json_encode($actual))
            );
        }
    }

    protected function getValueOfNode($node)
    {
        $json = $this->getJson();

        return $this->inspector->evaluate($json, $node);
    }

    protected function getJson()
    {
        return new Json($this->getContent());
    }

    protected function getContent()
    {
        return $this->restContext->getMink()->getSession()->getDriver()->getContent();
    }
}
