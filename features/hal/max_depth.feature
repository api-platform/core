Feature: Max depth handling
  In order to handle MaxChildDepth resources
  As a developer
  I need to be able to limit their depth with @maxDepth

  @createSchema
  Scenario: Create a resource with 1 level of descendants
    When I add "Accept" header equal to "application/hal+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/max_child_depths" with body:
    """
    {
      "name": "Fry's grandpa",
      "child": {
        "name": "Fry"
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "\/max_child_depths\/1"
        },
        "child": {
          "href": "\/max_child_depths\/2"
        }
      },
      "_embedded": {
        "child": {
          "_links": {
            "self": {
              "href": "\/max_child_depths\/2"
            }
          },
          "id": 2,
          "name": "Fry"
        }
      },
      "id": 1,
      "name": "Fry's grandpa"
    }
    """

  @dropSchema
  Scenario: Add a 2nd level of descendants
    When I add "Accept" header equal to "application/hal+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "max_child_depths/1" with body:
    """
    {
      "id": "/max_child_depths/1",
      "child": {
        "id": "/max_child_depths/2",
        "child": {
          "name": "Fry's child"
        }
      }
    }
    """
    And the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "_links": {
        "self": {
          "href": "\/max_child_depths\/1"
        },
        "child": {
          "href": "\/max_child_depths\/2"
        }
      },
      "_embedded": {
        "child": {
          "_links": {
            "self": {
              "href": "\/max_child_depths\/2"
            }
          },
          "id": 2,
          "name": "Fry"
        }
      },
      "id": 1,
      "name": "Fry's grandpa"
    }
    """
