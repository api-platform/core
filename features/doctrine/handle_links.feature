Feature: Use a link handler to retrieve a resource

  @createSchema
  Scenario: Get collection
    Given there are a few link handled dummies
    When I send a "GET" request to "/link_handled_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "hydra:totalItems" should be equal to 1

  @createSchema
  Scenario: Get item
    Given there are a few link handled dummies
    When I send a "GET" request to "/link_handled_dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "slug" should be equal to "foo"
