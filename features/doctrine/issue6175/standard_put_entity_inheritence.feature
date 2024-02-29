Feature: Update properties of a resource that are inherited with standard PUT operation

  @!mongodb
  @createSchema
  Scenario: Update properties of a resource that are inherited with standard PUT operation
    Given there is a dummy entity with a mapped superclass
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/dummy_mapped_subclasses/1" with body:
    """
    {
      "foo": "updated value"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/DummyMappedSubclass",
      "@id": "/dummy_mapped_subclasses/1",
      "@type": "DummyMappedSubclass",
      "id": 1,
      "foo": "updated value"
    }
    """
