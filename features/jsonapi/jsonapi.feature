Feature: JSONAPI support
  In order to use the JSONAPI hypermedia format
  As a client software developer
  I need to be able to retrieve valid HAL responses.

  @createSchema
  Scenario: Retrieve the API entrypoint
    When I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
    And the JSON node "links.self" should be equal to "/"
    And the JSON node "links.dummy" should be equal to "/dummies"

  Scenario: Test against jsonapi-validator
    When I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/dummies"
    Then I save the response
    And I valide it with jsonapi-validator

  Scenario: Create a third level
    When I add "Content-Type" header equal to "application/vnd.api+json"
    And I send a "POST" request to "/third_levels" with body:
    """
    {"level": 3}
    """
    Then the response status code should be 201

  Scenario: Create a related dummy
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/related_dummies" with body:
    """
    {"thirdLevel": "/third_levels/1"}
    """
    Then the response status code should be 201

  Scenario: Embed a relation in a parent object
    When I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
      "related": "/related_dummies/1"
    }
    """
    Then the response status code should be 201

  Scenario: Get the object with the embedded relation
    When I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/relation_embedders/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
          "relationships": {
              "related": {
                  "relationships": {
                      "thirdLevel": {
                          "level": 3
                      }
                  },
                  "symfony": "symfony"
              }
          },
          "krondstadt": "Krondstadt"
    }
    """

  @dropSchema
  Scenario: Get a collection
    When I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
          "links": {
              "self": "/dummies"
          },
          "meta": {
              "totalItems": 0,
              "itemsPerPage": 3
          }
      }
    """
