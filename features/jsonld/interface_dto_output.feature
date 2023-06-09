Feature: Resource should be able to take interface as output value

  @createSchema
  Scenario: I should be able to GET a collection of objects
    Given I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/json"
    When I send a "GET" request to "/entity_with_dto_outputs"
    And the JSON node "hydra:member[0].name" should exist
    And the JSON node "hydra:member[0].@type" should exist
    And the JSON node "hydra:member[0].@id" should exist
    And the JSON node "hydra:member[0].city" should not exist
