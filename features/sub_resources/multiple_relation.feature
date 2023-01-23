Feature: JSON-LD multi relation
  In order to use non-resource types
  As a developer
  I should be able to serialize types not mapped to an API resource.

  Background:
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"

  @createSchema
  @!mongodb
  Scenario: Get a multiple relation between to object
    Given there is a relationMultiple object
    When I send a "GET" request to "/dummy/1/relations/2"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelationMultiple",
      "@id": "/dummy/1/relations/2",
      "@type": "RelationMultiple",
      "id": 1,
      "first": "/dummies/1",
      "second": "/dummies/2"
    }
    """

  @!mongodb
  Scenario: Get all multiple relation of an object
    Given there is a dummy object with many multiple relation
    When I send a "GET" request to "/dummy/1/relations"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/RelationMultiple",
      "@id": "/dummy/1/relations",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/dummy/1/relations/2",
          "@type": "RelationMultiple",
          "id": 1,
          "first": "/dummies/1",
          "second": "/dummies/2"
        },
        {
          "@id": "/dummy/1/relations/3",
          "@type": "RelationMultiple",
          "id": 2,
          "first": "/dummies/1",
          "second": "/dummies/3"
        }
      ],
      "hydra:totalItems": 2
    }
    """
