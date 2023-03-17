Feature: Spec-compliant PUT support
  As a client software developer
  I need to be able to create or replace resources using the PUT HTTP method

  @createSchema
  @!mongodb
  Scenario: Get a correct status code when updating a resource that is not allowed to read nor to create
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/custom_puts/1" with body:
    """
    {
      "foo": "a",
      "bar": "b"
    }
    """
    Then the response status code should be 200
    And the response status code should not be 201
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomPut",
      "@id": "/custom_puts/1",
      "@type": "CustomPut",
      "id": 1,
      "foo": "a",
      "bar": "b"
    }
    """
