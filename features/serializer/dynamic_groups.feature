@!mongodb
Feature: Dynamic serialization context
  In order to customize the Resource representation dynamically
  As a developer
  I should be able to add and remove groups 

  @createSchema
  Scenario: 
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/relation_group_impact_on_collections/1"
    And the JSON node "related.title" should be equal to "foo"
