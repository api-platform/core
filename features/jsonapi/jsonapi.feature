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

  Scenario: Test empty list against jsonapi-validator
    When I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And print last JSON response
    And I save the response
    And I valide it with jsonapi-validator
    And the JSON node "data" should be an empty array

  @createSchema @current
  Scenario: Create a ThirdLevel
    When I add "Content-Type" header equal to "application/vnd.api+json"
    And I add "Accept" header equal to "application/vnd.api+json"
    And I send a "POST" request to "/third_levels" with body:
    """
    {
      "data": {
        "type": "third-level",
        "attributes": {
          "level": 3
        }
      }
    }
    """
    Then the response status code should be 201
    # TODO: The response should have a Location header identifying the newly created resource
    And print last JSON response
    And I save the response
    And I valide it with jsonapi-validator
    And the JSON node "data.id" should not be an empty string

  Scenario: Retrieve the collection
    When I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/third_levels"
    Then I save the response
    And I valide it with jsonapi-validator
    And print last JSON response

  Scenario: Retrieve the third level
    When I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/third_levels/1"
    Then I save the response
    And I valide it with jsonapi-validator
    And print last JSON response

  @current
  Scenario: Create a related dummy
    When I add "Content-Type" header equal to "application/vnd.api+json"
    And I add "Accept" header equal to "application/vnd.api+json"
    And I send a "POST" request to "/related_dummies" with body:
    """
    {
      "data": {
        "type": "related-dummy",
        "attributes": {
          "name": "sup yo",
          "age": 23
        },
        "relationships": {
          "thirdLevel": {
            "data": {
              "type": "third-level",
              "id": "1"
            }
          }
        }
      }
    }
    """
    Then print last JSON response
    And I save the response
    And I valide it with jsonapi-validator
    And the JSON node "data.id" should not be an empty string
    And the JSON node "data.attributes.name" should be equal to "sup yo"
    And the JSON node "data.attributes.age" should be equal to the number 23

  @dropSchema
  Scenario: Retrieve the related dummy
    When I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/third_levels/1"
    Then I save the response
    And I valide it with jsonapi-validator
    And print last JSON response

  # Scenario: Embed a relation in a parent object
  #   When I add "Content-Type" header equal to "application/json"
  #   And I send a "POST" request to "/relation_embedders" with body:
  #   """
  #   {
  #     "related": "/related_dummies/1"
  #   }
  #   """
  #   Then the response status code should be 201

  # Scenario: Get the object with the embedded relation
  #   When I add "Accept" header equal to "application/vnd.api+json"
  #   And I send a "GET" request to "/relation_embedders/1"
  #   Then the response status code should be 200
  #   And the response should be in JSON
  #   And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
  #   And the JSON should be equal to:
  #   """
  #   {
  #         "relationships": {
  #             "related": {
  #                 "relationships": {
  #                     "thirdLevel": {
  #                         "level": 3
  #                     }
  #                 },
  #                 "symfony": "symfony"
  #             }
  #         },
  #         "krondstadt": "Krondstadt"
  #   }
  #   """

  # Scenario: Get a collection
  #   When I add "Accept" header equal to "application/vnd.api+json"
  #   And I send a "GET" request to "/dummies"
  #   Then the response status code should be 200
  #   And the response should be in JSON
  #   And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
  #   And the JSON should be equal to:
  #   """
  #   {
  #         "links": {
  #             "self": "/dummies"
  #         },
  #         "meta": {
  #             "totalItems": 0,
  #             "itemsPerPage": 3
  #         }
  #     }
  #   """
