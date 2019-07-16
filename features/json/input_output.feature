Feature: JSON DTO input and output
  In order to use the API
  As a client software developer
  I need to be able to use DTOs on my resources as Input or Output objects.

  Background:
    Given I add "Accept" header equal to "application/json"
    And I add "Content-Type" header equal to "application/json"

  Scenario: Request a password reset
    And I send a "POST" request to "/users/password_reset_request" with body:
    """
    {
      "email": "user@example.com"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "emailSentAt": "2019-07-05T15:44:00+00:00"
    }
    """

  Scenario: Request a password reset for a non-existent user
    And I send a "POST" request to "/users/password_reset_request" with body:
    """
    {
      "email": "does-not-exist@example.com"
    }
    """
    Then the response status code should be 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the JSON node "detail" should be equal to "User does not exist."
