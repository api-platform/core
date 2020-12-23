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
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behatch\Context\RestContext;
use Behatch\HttpCall\Request;
use GraphQL\Type\Introspection;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Context for GraphQL.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class GraphqlContext implements Context
{
    /**
     * @var RestContext
     */
    private $restContext;

    /**
     * @var array
     */
    private $graphqlRequest;

    /**
     * @var int
     */
    private $graphqlLine;

    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

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
     * @When I have the following GraphQL request:
     */
    public function IHaveTheFollowingGraphqlRequest(PyStringNode $request)
    {
        $this->graphqlRequest = ['query' => $request->getRaw()];
        $this->graphqlLine = $request->getLine();
    }

    /**
     * @When I send the following GraphQL request:
     */
    public function ISendTheFollowingGraphqlRequest(PyStringNode $request)
    {
        $this->IHaveTheFollowingGraphqlRequest($request);
        $this->sendGraphqlRequest();
    }

    /**
     * @When I send the GraphQL request with variables:
     */
    public function ISendTheGraphqlRequestWithVariables(PyStringNode $variables)
    {
        $this->graphqlRequest['variables'] = $variables->getRaw();
        $this->sendGraphqlRequest();
    }

    /**
     * @When I send the GraphQL request with operationName :operationName
     */
    public function ISendTheGraphqlRequestWithOperation(string $operationName)
    {
        $this->graphqlRequest['operationName'] = $operationName;
        $this->sendGraphqlRequest();
    }

    /**
     * @Given I have the following file(s) for a GraphQL request:
     */
    public function iHaveTheFollowingFilesForAGraphqlRequest(TableNode $table)
    {
        $files = [];

        foreach ($table->getHash() as $row) {
            if (!isset($row['name'], $row['file'])) {
                throw new \InvalidArgumentException('You must provide a "name" and "file" column in your table node.');
            }

            $files[$row['name']] = $this->restContext->getMinkParameter('files_path').\DIRECTORY_SEPARATOR.$row['file'];
        }

        $this->graphqlRequest['files'] = $files;
    }

    /**
     * @Given I have the following GraphQL multipart request map:
     */
    public function iHaveTheFollowingGraphqlMultipartRequestMap(PyStringNode $string)
    {
        $this->graphqlRequest['map'] = $string->getRaw();
    }

    /**
     * @When I send the following GraphQL multipart request operations:
     */
    public function iSendTheFollowingGraphqlMultipartRequestOperations(PyStringNode $string)
    {
        $params = [];
        $params['operations'] = $string->getRaw();
        $params['map'] = $this->graphqlRequest['map'];

        $this->request->setHttpHeader('Content-type', 'multipart/form-data'); // @phpstan-ignore-line
        $this->request->send('POST', '/graphql', $params, $this->graphqlRequest['files']); // @phpstan-ignore-line
    }

    /**
     * @When I send the query to introspect the schema
     */
    public function ISendTheQueryToIntrospectTheSchema()
    {
        $this->graphqlRequest = ['query' => Introspection::getIntrospectionQuery()];
        $this->sendGraphqlRequest();
    }

    /**
     * @Then the GraphQL field :fieldName is deprecated for the reason :reason
     */
    public function theGraphQLFieldIsDeprecatedForTheReason(string $fieldName, string $reason)
    {
        foreach (json_decode($this->request->getContent(), true)['data']['__type']['fields'] as $field) { // @phpstan-ignore-line
            if ($fieldName === $field['name'] && $field['isDeprecated'] && $reason === $field['deprecationReason']) {
                return;
            }
        }

        throw new ExpectationFailedException(sprintf('The field "%s" is not deprecated.', $fieldName));
    }

    private function sendGraphqlRequest()
    {
        $this->restContext->iSendARequestTo('GET', '/graphql?'.http_build_query($this->graphqlRequest));
    }
}
