Feature: Max depth handling
  In order to handle MaxChildDepth resources
  As a developer
  I need to be able to limit their depth with @maxDepth

  @createSchema
  Scenario: Create a resource with 1 level of descendants
    When I add "Accept" header equal to "application/hal+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/max_depth_eager_dummies" with body:
    """
    {
      "name": "level 1",
      "child": {
        "name": "level 2"
      }
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    Then the JSON node "_embedded" should exist
    Then the JSON node "_embedded.child" should exist
    Then the JSON node "_embedded.child._embedded" should not exist

  Scenario: Create a resource with 2 levels of descendants
    When I add "Accept" header equal to "application/hal+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "POST" request to "/max_depth_eager_dummies" with body:
    """
    {
      "name": "level 1",
      "child": {
        "name": "level 2",
        "child": {
          "name": "level 3"
        }
      }
    }
    """
    And the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    Then the JSON node "_embedded" should exist
    Then the JSON node "_embedded.child" should exist
    Then the JSON node "_embedded.child._embedded" should not exist

  Scenario: Create a resource with 1 levels of descendants then add a 2nd level of descendants when eager fetching is disabled
    Given there is a max depth dummy with 1 level of descendants
    When I add "Accept" header equal to "application/hal+json"
    And I add "Content-Type" header equal to "application/json"
    And I send a "PUT" request to "max_depth_dummies/1" with body:
    """
    {
      "id": "/max_depth_dummies/1",
      "child": {
        "id": "/max_depth_dummies/2",
        "child": {
          "name": "level 3"
        }
      }
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/hal+json; charset=utf-8"
    Then the JSON node "_embedded" should exist
    Then the JSON node "_embedded.child" should exist
    Then the JSON node "_embedded.child._embedded" should not exist
