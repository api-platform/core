Feature: Operation support
  In order to make the API fitting custom need
  As an API developer
  I need to be able to add custom operations and remove built-in ones

  Scenario: Access custom operations
    When I send a "GET" request to "/relation_embedders/42/custom"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    "This is a custom action for 42."
    """
