Feature: Documentation support
  In order to play with GraphQL
  As a client software developer
  I want to reach the GraphQL documentation

    Scenario: Retrieve the OpenAPI documentation
    Given I add "Accept" header equal to "text/html"
    And I send a "GET" request to "/graphql"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "text/html; charset=utf-8"
