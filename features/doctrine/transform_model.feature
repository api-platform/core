Feature: Use an entity or document transformer to return the correct ressource

  @createSchema
  @!mongodb
  Scenario: Get transformed collection from entities
    Given there is a TransformedDummy for date '2025-01-01'
    When I send a "GET" request to "/transformed_dummy_entity_ressources"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "hydra:totalItems" should be equal to 1

  @!mongodb
  Scenario: Get transform item from entity
    Given there is a TransformedDummy for date '2025-01-01'
    When I send a "GET" request to "/transformed_dummy_entity_ressources/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "year" should exist
    And the JSON node year should be equal to "2025"

  @!mongodb
  Scenario: Post new entity from transformed resource
    Given I add "Content-type" header equal to "application/ld+json"
    When I send a "POST" request to "/transformed_dummy_entity_ressources" with body:
    """
      {
        "year" : 2020
      }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON node "year" should be equal to "2020"

  @!mongodb
  Scenario: Patch entity from transformed resource
    Given there is a TransformedDummy for date '2025-01-01'
    And I add "Content-type" header equal to "application/merge-patch+json"
    When I send a "PATCH" request to "/transformed_dummy_entity_ressources/1" with body:
    """
      {
        "year" : 2020
      }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "year" should be equal to "2020"

  @createSchema
  @mongodb
  Scenario: Get collection from documents
    Given there is a TransformedDummy for date '2025-01-01'
    When I send a "GET" request to "/transformed_dummy_document_ressources"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "hydra:totalItems" should be equal to 1

  @mongodb
  Scenario: Get item from document
    Given there is a TransformedDummy for date '2025-01-01'
    When I send a "GET" request to "/transformed_dummy_document_ressources/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "year" should exist
    And the JSON node year should be equal to "2025"
