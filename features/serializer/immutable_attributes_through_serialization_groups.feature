Feature: Make attributes of an entity immutable with serialization groups
  In order to have a resource with an immutable attribute
  As a client
  I can set the attribute while creating a resource, but cannot update it later

  @createSchema
  Scenario: Set an immutable attribute while creating a resource and try to change it
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummy_entity_with_immutables" with body:
    """
    {
      "immutableName": "foo",
      "mutableWebsite": "katzensaft.de"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyEntityWithImmutable",
      "@id": "/dummy_entity_with_immutables/foo",
      "@type": "DummyEntityWithImmutable",
      "immutableName": "foo",
      "mutableWebsite": "katzensaft.de"
    }
    """
    Then I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummy_entity_with_immutables/foo" with body:
    """
    {
      "immutableName": "bar",
      "mutableWebsite": "katzensaft.de"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyEntityWithImmutable",
      "@id": "/dummy_entity_with_immutables/foo",
      "@type": "DummyEntityWithImmutable",
      "immutableName": "foo",
      "mutableWebsite": "katzensaft.de"
    }
    """
