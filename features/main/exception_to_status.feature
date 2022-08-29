Feature: Using exception_to_status config
  As an API developer
  I can customize the status code returned if the application throws an exception

  @createSchema
  Scenario: Configure status code via the operation exceptionToStatus to map custom NotFound error to 404
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/dummy_exception_to_statuses/123"
    Then the response status code should be 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

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
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  Scenario: Configure status code via the config file to map FilterValidationException to 400
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/dummy_exception_to_statuses"
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
