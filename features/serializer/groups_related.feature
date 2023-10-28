@!mongodb
Feature: Groups to embed relations
  In order to show embed relations on a Resource
  As a client software developer
  I need to set up groups on the Resource embed properties

  Scenario: Get a single resource
    When I send a "GET" request to "/relation_group_impact_on_collections/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "related.title" should be equal to "foo"

  Scenario: Get a collection resource not impacted by groups
    When I send a "GET" request to "/relation_group_impact_on_collections"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON node "hydra:member[0].related" should be equal to "/relation_group_impact_on_collection_relations/1"
