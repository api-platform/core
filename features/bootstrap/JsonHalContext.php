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
use Behatch\Json\Json;
use JsonSchema\Validator;
use PHPUnit\Framework\ExpectationFailedException;

final class JsonHalContext implements Context
{
    /**
     * @var RestContext
     */
    private $restContext;
    private $validator;
    private $schemaFile;

    public function __construct(string $schemaFile)
    {
        if (!is_file($schemaFile)) {
            throw new \InvalidArgumentException('The JSON HAL schema doesn\'t exist.');
        }

        $this->validator = new Validator();
        $this->schemaFile = $schemaFile;
    }

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
     * @Then the JSON should be valid according to the JSON HAL schema
     */
    public function theJsonShouldBeValidAccordingToTheJsonHALSchema()
    {
        $json = $this->getJson()->getContent();
        $this->validator->validate($json, (object) ['$ref' => "file://{$this->schemaFile}"]);

        if (!$this->validator->isValid()) {
            throw new ExpectationFailedException(sprintf('The JSON is not valid according to the HAL+JSON schema.'));
        }
    }

    private function getJson()
    {
        return new Json($this->getContent());
    }

    private function getContent()
    {
        return $this->restContext->getMink()->getSession()->getDriver()->getContent();
    }
}
