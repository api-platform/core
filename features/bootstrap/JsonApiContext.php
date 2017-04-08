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

final class JsonApiContext implements Context
{
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
     * @Then I save the response
     */
    public function iSaveTheResponse()
    {
        $content = $this->restContext->getMink()->getSession()->getDriver()->getContent();
        if (null === ($decoded = json_decode($content))) {
            throw new \RuntimeException('JSON response seems to be invalid');
        }
        file_put_contents(dirname(__FILE__).'/response.json', $content);
    }

    /**
     * @Then I valide it with jsonapi-validator
     */
    public function iValideItWithJsonapiValidator()
    {
        return 'response.json is valid JSON API.' === exec(sprintf('cd %s && jsonapi-validator -f response.json', dirname(__FILE__)));
    }
}
