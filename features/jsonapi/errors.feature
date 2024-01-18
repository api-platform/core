@!mongodb
Feature: JSON API error handling
  In order to be able to handle error client side
  As a client software developer
  I need to retrieve an JSON API serialization of errors

  Background:
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/vnd.api+json"

  @createSchema
  Scenario: Get a validation error on an attribute
    When I send a "POST" request to "/dummy_problems" with body:
    """
    {
      "data": {
        "type": "dummy",
        "attributes": {}
      }
    }
    """
    Then the response status code should be 422
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be equal to:
    """
    {
      "errors": [
        {
          "detail": "This value should not be blank.",
          "source": {
            "pointer": "data/attributes/name"
          }
        }
      ]
    }
    """

  Scenario: Get an rfc 7807 error
    When I send a "POST" request to "/exception_problems" with body:
    """
    {}
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
    And the JSON node "errors[0].title" should be equal to "An error occurred"
    And the JSON node "errors[0].status" should be equal to 400
    And the JSON node "errors[0].detail" should exist
    And the JSON node "errors[0].type" should exist

  Scenario: Get an rfc 7807 error
    When I send a "POST" request to "/does_not_exist" with body:
    """
    {}
    """
    Then the response status code should be 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
    And the JSON node "errors[0].title" should be equal to "An error occurred"
    And the JSON node "errors[0].status" should be equal to 404
    And the JSON node "errors[0].detail" should exist
    And the JSON node "errors[0].type" should exist
