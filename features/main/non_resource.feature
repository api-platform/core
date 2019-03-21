Feature: Non-resources handling
  In order to handle use non-resource types
  As a developer
  I should be able serialize types not mapped to an API resource.

  Scenario: Get a resource containing a raw object
    When  I send a "GET" request to "/contain_non_resources/1"
    Then the JSON should be equal to:
    """
    {
        "@context": "/contexts/ContainNonResource",
        "@id": "/contain_non_resources/1",
        "@type": "ContainNonResource",
        "id": 1,
        "nested": {
            "@id": "/contain_non_resources/1-nested",
            "@type": "ContainNonResource",
            "id": "1-nested",
            "nested": null,
            "notAResource": {
                "foo": "f2",
                "bar": "b2"
            }
        },
        "notAResource": {
            "foo": "f1",
            "bar": "b1"
        }
    }
    """

  @!mongodb
  @createSchema
  Scenario: Create a resource that has a non-resource relation.
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/non_relation_resources" with body:
    """
    {"relation": {"foo": "test"}}
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/NonRelationResource",
      "@id": "/non_relation_resources/1",
      "@type": "NonRelationResource",
      "relation": {
        "foo": "test"
      },
      "id": 1
    }
    """
