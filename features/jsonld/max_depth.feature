Feature: Max depth handling
  In order to handle MaxDepthDummy resources
  As a developer
  I need to be able to limit their depth with @maxDepth

  @createSchema
  Scenario: Create a resource with 1 level of descendants
    When I add "Content-Type" header equal to "application/ld+json"
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
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    Then the JSON node "child" should exist
    Then the JSON node "child.name" should be equal to "level 2"

  Scenario: Add a 2nd level of descendants
    When I add "Content-Type" header equal to "application/ld+json"
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
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    Then the JSON node "child" should exist
    Then the JSON node "child.name" should be equal to "level 2"
    Then the JSON node "child.child" should not exist
