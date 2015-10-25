Feature: Circular references handling
  In order to handle circular references
  As a developer
  I should be able to catch circular references.

  @createSchema
  @dropSchema
  Scenario: Create a circular reference
    When I send a "POST" request to "/circular_references" with body:
    """
    {}
    """
    And I send a "PUT" request to "/circular_references/1" with body:
    """
    {
      "parent": "/circular_references/1"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CircularReference",
      "@id": "/circular_references/1",
      "@type": "CircularReference",
      "parent": "/circular_references/1",
      "children": [
        "/circular_references/1"
      ]
    }
    """
