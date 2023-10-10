Feature: Using exception_to_status config
  As an API developer
  I can customize the status code returned if the application throws an exception

  @createSchema
  @!mongodb
  Scenario: Configure status code via the operation exceptionToStatus to map custom NotFound error to 404
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/dummy_exception_to_statuses/123"
    Then the response status code should be 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"

  @!mongodb
  Scenario: Configure status code via the resource exceptionToStatus to map custom NotFound error to 400
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummy_exception_to_statuses/123" with body:
    """
    {
        "name": "black"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"

  @!mongodb
  Scenario: Configure status code via the config file to map FilterValidationException to 400
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/dummy_exception_to_statuses"
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"

  @!mongodb
  Scenario: Override validation exception status code from delete operation
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "DELETE" request to "/error_with_overriden_status/1"
    Then the response status code should be 403
    And the JSON node "status" should be equal to 403
