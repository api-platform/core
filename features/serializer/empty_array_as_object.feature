Feature: Serialize empty array as object
  In order to have a coherent JSON representation
  As a developer
  I should be able to serialize some empty array properties as objects

  @createSchema
  Scenario: Get a resource with empty array properties as objects
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/empty_array_as_objects/5"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/EmptyArrayAsObject",
      "@id": "/empty_array_as_objects/6",
      "@type": "EmptyArrayAsObject",
      "id": 6,
      "emptyArray": [],
      "emptyArrayAsObject": {},
      "arrayObjectAsArray": [],
      "arrayObject": {},
      "stringArray": [
        "foo",
        "bar"
      ],
      "objectArray": {
        "foo": 67,
        "bar": "baz"
      }
    }
    """
