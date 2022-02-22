Feature: State processor
  In order to have different resource representations in the request body and in in the response body
  As a software developer
  I need to be able to set classes which aren't marked with #[ApiResource] as input and output

  Background:
    Given I add "Accept" header equal to "application/json"
    And I add "Content-Type" header equal to "application/json"

  Scenario: Request a password reset
    And I send a "POST" request to "/user-reset-password" with body:
    """
    {
      "email": "user@example.com"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the JSON node "email" should be equal to "user@example.com"

  Scenario: Request a password reset for a non-existent user
    And I send a "POST" request to "/user-reset-password" with body:
    """
    {
      "email": "does-not-exist@example.com"
    }
    """
    Then the response status code should be 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
