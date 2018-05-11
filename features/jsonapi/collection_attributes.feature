Feature: JSON API collections support
  In order to use the JSON API hypermedia format
  As a client software developer
  I need to be able to retrieve valid JSON API responses for collection attributes on entities.

  Background:
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/vnd.api+json"

  @createSchema
  Scenario: Correctly serialize a collection
    Given there is a CircularReference
    When I send a "GET" request to "/circular_references/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON should be equal to:
    """
    {
      "data": {
        "id": "/circular_references/1",
        "type": "CircularReference",
        "relationships": {
          "parent": {
            "data": {
              "type": "CircularReference",
              "id": "/circular_references/1"
            }
          },
          "children": {
            "data": [
              {
                "type": "CircularReference",
                "id": "/circular_references/1"
              },
              {
                "type": "CircularReference",
                "id": "/circular_references/2"
              }
            ]
          }
        }
      }
    }
    """
