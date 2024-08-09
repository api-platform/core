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
use Behat\Gherkin\Node\TableNode;
use Behatch\Context\RestContext;
use Behatch\HttpCall\Request;
use GraphQL\Error\Error;
use GraphQL\Type\Introspection;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Context for GraphQL.
 *
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class GraphqlContext implements Context
{
    private ?RestContext $restContext = null;
    private ?JsonContext $jsonContext = null;

    private array $graphqlRequest;

    private ?int $graphqlLine = null; // @phpstan-ignore-line

    public function __construct(private readonly Request $request)
    {
    }

    /**
     * Gives access to the Behatch context.
     *
     * @BeforeScenario
     */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        /** @var InitializedContextEnvironment $environment */
        $environment = $scope->getEnvironment();
        /** @var RestContext $restContext */
        $restContext = $environment->getContext(RestContext::class);
        $this->restContext = $restContext;
        /** @var JsonContext $jsonContext */
        $jsonContext = $environment->getContext(JsonContext::class);
        $this->jsonContext = $jsonContext;
    }

    /**
     * @When I have the following GraphQL request:
     */
    public function IHaveTheFollowingGraphqlRequest(PyStringNode $request): void
    {
        $this->graphqlRequest = ['query' => $request->getRaw()];
        $this->graphqlLine = $request->getLine();
    }

    /**
     * @When I send the following GraphQL request:
     */
    public function ISendTheFollowingGraphqlRequest(PyStringNode $request): void
    {
        $this->IHaveTheFollowingGraphqlRequest($request);
        $this->sendGraphqlRequest();
    }

    /**
     * @When I send the GraphQL request with variables:
     */
    public function ISendTheGraphqlRequestWithVariables(PyStringNode $variables): void
    {
        $this->graphqlRequest['variables'] = $variables->getRaw();
        $this->sendGraphqlRequest();
    }

    /**
     * @When I send the GraphQL request with operationName :operationName
     */
    public function ISendTheGraphqlRequestWithOperation(string $operationName): void
    {
        $this->graphqlRequest['operationName'] = $operationName;
        $this->sendGraphqlRequest();
    }

    /**
     * @Given I have the following file(s) for a GraphQL request:
     */
    public function iHaveTheFollowingFilesForAGraphqlRequest(TableNode $table): void
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
    public function iHaveTheFollowingGraphqlMultipartRequestMap(PyStringNode $string): void
    {
        $this->graphqlRequest['map'] = $string->getRaw();
    }

    /**
     * @When I send the following GraphQL multipart request operations:
     */
    public function iSendTheFollowingGraphqlMultipartRequestOperations(PyStringNode $string): void
    {
        $params = [];
        $params['operations'] = $string->getRaw();
        $params['map'] = $this->graphqlRequest['map'];

        $this->request->setHttpHeader('Content-type', 'multipart/form-data');
        $this->request->send('POST', '/graphql', $params, $this->graphqlRequest['files']);
    }

    /**
     * @When I send the query to introspect the schema
     */
    public function ISendTheQueryToIntrospectTheSchema(): void
    {
        $this->graphqlRequest = ['query' => Introspection::getIntrospectionQuery()];
        $this->sendGraphqlRequest();
    }

    /**
     * @Then the GraphQL field :fieldName is deprecated for the reason :reason
     */
    public function theGraphQLFieldIsDeprecatedForTheReason(string $fieldName, string $reason): void
    {
        foreach (json_decode($this->request->getContent(), true, 512, \JSON_THROW_ON_ERROR)['data']['__type']['fields'] as $field) {
            if ($fieldName === $field['name'] && $field['isDeprecated'] && $reason === $field['deprecationReason']) {
                return;
            }
        }

        throw new ExpectationFailedException(\sprintf('The field "%s" is not deprecated.', $fieldName));
    }

    /**
     * @Then the GraphQL debug message should be equal to :expectedDebugMessage
     */
    public function theGraphQLDebugMessageShouldBeEqualTo(string $expectedDebugMessage): void
    {
        $jsonNode = 'errors[0].extensions.debugMessage';
        // graphql-php < 15
        if (\defined(Error::class.'::CATEGORY_INTERNAL')) {
            $jsonNode = 'errors[0].debugMessage';
        }

        $this->jsonContext->theJsonNodeShouldBeEqualTo($jsonNode, $expectedDebugMessage);
    }

    private function sendGraphqlRequest(): void
    {
        $this->restContext->iSendARequestTo('GET', '/graphql?'.http_build_query($this->graphqlRequest));
    }
}
