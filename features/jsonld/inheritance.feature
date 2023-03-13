Feature: Inheritance with correct IRIs
  In order to fix (https://github.com/api-platform/core/issues/5438)

  Scenario: Get the collection of people with its employees
    When I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/people_5438"
    Then print last JSON response

  Scenario: Get the collection of people with its employees
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/people_5438"
    Then print last JSON response
