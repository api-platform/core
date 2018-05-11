Feature: JSON API basic support
  In order to use the JSON API hypermedia format
  As a client software developer
  I need to be able to retrieve valid JSON API responses.

  Background:
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/vnd.api+json"

  @createSchema
  Scenario: Retrieve the API entrypoint
    When I send a "GET" request to "/"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/vnd.api+json; charset=utf-8"
    And the JSON node "links.self" should be equal to "http://example.com/"
    And the JSON node "links.dummy" should be equal to "http://example.com/dummies"

  Scenario: Test empty list against JSON API schema
    When I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data" should be an empty array

  Scenario: Create a ThirdLevel
    When I send a "POST" request to "/third_levels" with body:
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
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data.id" should not be an empty string

  Scenario: Retrieve the collection
    When I send a "GET" request to "/third_levels"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema

  Scenario: Retrieve the third level
    When I send a "GET" request to "/third_levels/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema

  Scenario: Create a related dummy
    When I send a "POST" request to "/related_dummies" with body:
    """
    {
      "data": {
        "type": "related-dummy",
        "attributes": {
          "name": "John Doe",
          "age": 23
        },
        "relationships": {
          "thirdLevel": {
            "data": {
              "type": "third-level",
              "id": "/third_levels/1"
            }
          }
        }
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data.id" should not be an empty string
    And the JSON node "data.attributes.name" should be equal to "John Doe"
    And the JSON node "data.attributes.age" should be equal to the number 23

  Scenario: Create a dummy with relations
    Given there is a RelatedDummy
    When I send a "POST" request to "/dummies" with body:
    """
    {
      "data": {
        "type": "dummy",
        "attributes": {
          "name": "Dummy with relations",
          "dummyDate": "2015-03-01T10:00:00+00:00"
        },
        "relationships": {
          "relatedDummy": {
            "data": {
              "type": "related-dummy",
              "id": "/related_dummies/2"
            }
          },
          "relatedDummies": {
            "data": [
              {
                "type": "related-dummy",
                "id": "/related_dummies/1"
              },
              {
                "type": "related-dummy",
                "id": "/related_dummies/2"
              }
            ]
          }
        }
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data.relationships.relatedDummies.data" should have 2 elements
    And the JSON node "data.relationships.relatedDummy.data.id" should be equal to "/related_dummies/2"

  Scenario: Update a resource with a many-to-many relationship via PATCH
    When I send a "PATCH" request to "/dummies/1" with body:
    """
    {
      "data": {
        "type": "dummy",
        "relationships": {
          "relatedDummy": {
            "data": {
              "type": "related-dummy",
              "id": "/related_dummies/1"
            }
          },
          "relatedDummies": {
            "data": [
              {
                "type": "related-dummy",
                "id": "/related_dummies/2"
              }
            ]
          }
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data.relationships.relatedDummies.data" should have 1 elements
    And the JSON node "data.relationships.relatedDummy.data.id" should be equal to "/related_dummies/1"

  Scenario: Create a related dummy with an empty relationship
    When I send a "POST" request to "/related_dummies" with body:
    """
    {
      "data": {
        "type": "related-dummy",
        "attributes": {
          "name": "John Doe"
        },
        "relationships": {
          "thirdLevel": {
            "data": null
          }
        }
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema

  Scenario: Retrieve a collection with relationships
    When I send a "GET" request to "/related_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data[0].relationships.thirdLevel.data.id" should be equal to "/third_levels/1"

  Scenario: Retrieve the related dummy
    When I send a "GET" request to "/related_dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be equal to:
    """
    {
      "data": {
        "id": "/related_dummies/1",
        "type": "RelatedDummy",
        "attributes": {
          "_id": 1,
          "name": "John Doe",
          "symfony": "symfony",
          "dummyDate": null,
          "dummyBoolean": null,
          "embeddedDummy": [],
          "age": 23
        },
        "relationships": {
          "thirdLevel": {
            "data": {
              "type": "ThirdLevel",
              "id": "/third_levels/1"
            }
          }
        }
      }
    }
    """

  Scenario: Update a resource via PATCH
    When I send a "PATCH" request to "/related_dummies/1" with body:
    """
    {
      "data": {
        "type": "related-dummy",
        "attributes": {
          "name": "Jane Doe"
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data.id" should not be an empty string
    And the JSON node "data.attributes.name" should be equal to "Jane Doe"
    And the JSON node "data.attributes.age" should be equal to the number 23

  Scenario: Embed a relation in a parent object
    When I send a "POST" request to "/relation_embedders" with body:
    """
    {
      "data": {
        "relationships": {
          "related": {
            "data": {
              "type": "related-dummy",
              "id": "/related_dummies/1"
            }
          }
        }
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data.id" should not be an empty string
    And the JSON node "data.attributes.krondstadt" should be equal to "Krondstadt"
    And the JSON node "data.relationships.related.data.id" should be equal to "/related_dummies/1"
