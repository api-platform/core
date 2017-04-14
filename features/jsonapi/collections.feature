Feature: JSON API collections support
  In order to use the JSON API hypermedia format
  As a client software developer
  I need to be able to retrieve valid JSON API responses for collection attributes on entities.

  @createSchema
  @dropSchema
  Scenario: Correctly serialize a collection
    When I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/vnd.api+json"
    And I send a "POST" request to "/circular_references" with body:
    """
    {
      "data": {}
    }
    """
    And I validate it with jsonapi-validator
    And I send a "PATCH" request to "/circular_references/1" with body:
    """
    {
      "data": {
        "relationships": {
          "parent": {
            "data": {
              "type": "CircularReference",
              "id": "1"
            }
          }
        }
      }
    }
    """
    And I validate it with jsonapi-validator
    And I send a "POST" request to "/circular_references" with body:
    """
    {
      "data": {
        "relationships": {
          "parent": {
            "data": {
              "type": "CircularReference",
              "id": "1"
            }
          }
        }
      }
    }
    """
    And I validate it with jsonapi-validator
    And I send a "GET" request to "/circular_references/1"
    And I validate it with jsonapi-validator
    # And the JSON should be equal to:
    # """
    # {
    #   "@context": "/contexts/CircularReference",
    #   "@id": "/circular_references/1",
    #   "@type": "CircularReference",
    #   "parent": "/circular_references/1",
    #   "children": [
    #     "/circular_references/1"
    #   ]
    # }
    # """
