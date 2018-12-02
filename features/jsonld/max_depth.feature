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
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/MaxDepthEagerDummy",
      "@id": "/max_depth_eager_dummies/1",
      "@type": "MaxDepthEagerDummy",
      "id": 1,
      "name": "level 1",
      "child": {
        "@id": "/max_depth_eager_dummies/2",
        "@type": "MaxDepthEagerDummy",
        "id": 2,
        "name": "level 2"
      }
    }
    """

  Scenario: Add a 2nd level of descendants
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "max_depth_eager_dummies/1" with body:
    """
    {
      "@id": "/max_depth_eager_dummies/1",
      "child": {
        "@id": "/max_depth_eager_dummies/2",
        "child": {
          "name": "level 3"
        }
      }
    }
    """
    And the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/MaxDepthEagerDummy",
      "@id": "/max_depth_eager_dummies/1",
      "@type": "MaxDepthEagerDummy",
      "id": 1,
      "name": "level 1",
      "child": {
        "@id": "/max_depth_eager_dummies/2",
        "@type": "MaxDepthEagerDummy",
        "id": 2,
        "name": "level 2"
      }
    }
    """

