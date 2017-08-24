Feature: Max depth handling
  In order to handle recursive resources
  As a developer
  I need to be able to limit their depth with @maxDepth

  @createSchema
  Scenario: Create a non-recursive resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/recursives" with body:
    """
    {
      "name": "Fry's grandpa",
      "child": {
        "name": "Fry"
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Recursive",
      "@id": "/recursives/1",
      "@type": "Recursive",
      "id": 1,
      "name": "Fry's grandpa",
      "child": {
        "@id": "/recursive_children/1",
        "@type": "RecursiveChild",
        "id": 1,
        "name": "Fry",
        "parent": null
      }
    }
    """

  @dropSchema
  Scenario: Make the resource recursive
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "recursives/1" with body:
    """
    {
      "@id": "/recursives/1",
      "child": {
        "@id": "/recursive_children/1",
        "parent": "/recursives/1"
      }
    }
    """
    And the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Recursive",
      "@id": "/recursives/1",
      "@type": "Recursive",
      "id": 1,
      "name": "Fry's grandpa",
      "child": {
        "@id": "/recursive_children/1",
        "@type": "RecursiveChild",
        "id": 1,
        "name": "Fry",
        "parent": "/recursives/1"
      }
    }
    """
