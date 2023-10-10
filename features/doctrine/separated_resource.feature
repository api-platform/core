Feature: Use state options to use an entity that is not a resource
  In order to work with resources and a doctrine entity
  As a client software developer
  I need to retrieve a CRUD by specifying an entity class

  @!mongodb
  @createSchema
  Scenario: Get collection
    Given there are 5 separated entities
    When I send a "GET" request to "/separated_entities"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    Then the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/SeparatedEntity"},
        "@id": {"pattern": "^/separated_entities"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object"
          }
        },
        "hydra:totalItems": {"type":"number"},
        "hydra:view": {
          "type": "object"
        }
      }
    }
    """

  @!mongodb
  @createSchema
  Scenario: Get ordered collection
    Given there are 5 separated entities
    When I send a "GET" request to "/separated_entities?order[value]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member[0].value" should be equal to "5"

  @!mongodb
  @createSchema
  Scenario: Get item
    Given there are 5 separated entities
    When I send a "GET" request to "/separated_entities/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  @!mongodb
  @createSchema
  Scenario: Get all EntityClassAndCustomProviderResources
    Given there are 1 separated entities
    When I send a "GET" request to "/entityClassAndCustomProviderResources"
    Then the response status code should be 200

  @!mongodb
  @createSchema
  Scenario: Get one EntityClassAndCustomProviderResource
    Given there are 1 separated entities
    When I send a "GET" request to "/entityClassAndCustomProviderResources/1"
    Then the response status code should be 200

  @mongodb
  @createSchema
  Scenario: Get collection
    Given there are 5 separated entities
    When I send a "GET" request to "/separated_documents"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    Then the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "@context": {"pattern": "^/contexts/SeparatedDocument"},
        "@id": {"pattern": "^/separated_documents"},
        "@type": {"pattern": "^hydra:Collection$"},
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object"
          }
        },
        "hydra:totalItems": {"type":"number"},
        "hydra:view": {
          "type": "object"
        }
      }
    }
    """

  @mongodb
  @createSchema
  Scenario: Get ordered collection
    Given there are 5 separated entities
    When I send a "GET" request to "/separated_documents?order[value]=desc"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member[0].value" should be equal to "5"

  @mongodb
  @createSchema
  Scenario: Get item
    Given there are 5 separated entities
    When I send a "GET" request to "/separated_documents/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
