Feature: Resource with constructor deserializable
  In order to build non anemic resource object
  As a developer
  I should be able to deserialize data into objects with constructors

  @createSchema
  Scenario: post a resource built with constructor
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_entity_with_constructors" with body:
    """
    {
      "foo": "hello",
      "bar": "world",
      "items": [
        {
          "foo": "bar"
        }
      ]
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyEntityWithConstructor",
      "@id": "/dummy_entity_with_constructors/1",
      "@type": "DummyEntityWithConstructor",
      "id": 1,
      "foo": "hello",
      "bar": "world",
      "items": [
        {
          "foo": "bar"
        }
      ],
      "baz": null
    }
    """
