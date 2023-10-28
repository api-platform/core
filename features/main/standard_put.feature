Feature: Spec-compliant PUT support
  As a client software developer
  I need to be able to create or replace resources using the PUT HTTP method

  @createSchema
  Scenario: Create a new resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/standard_puts/5" with body:
    """
    {
      "foo": "a",
      "bar": "b"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/StandardPut",
      "@id": "/standard_puts/5",
      "@type": "StandardPut",
      "id": 5,
      "foo": "a",
      "bar": "b"
    }
    """

  Scenario: Replace an existing resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/standard_puts/5" with body:
    """
    {
      "foo": "c"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/StandardPut",
      "@id": "/standard_puts/5",
      "@type": "StandardPut",
      "id": 5,
      "foo": "c",
      "bar": ""
    }
    """
