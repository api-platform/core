Feature: Disable Id generation on anonymous resource collections

  @!mongodb
  @createSchema
  Scenario: Get embed collection without ids
    When I add "Accept" header equal to "application/ld+json"
    And I send a "GET" request to "/disable_id_generation_collection"
    Then the response status code should be 200
    Then the JSON node "disableIdGenerationItems[0].@id" should not exist
