@sqlite
@customTagCollector
@disableForSymfonyLowest
Feature: Cache invalidation through HTTP Cache tags (custom TagCollector service)
  In order to have a fast API
  As an API software developer
  I need to store API responses in a cache

  @createSchema
  Scenario: Create a dummy resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
    }
    """
    Then the response status code should be 201
    And the header "Cache-Tags" should not exist

  Scenario: TagCollector can identify $object (IRI is overriden with custom logic)
    When I send a "GET" request to "/relation_embedders/1"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/RE/1#anotherRelated,/RE/1#related,/RE/1"

  Scenario: Create some embedded resources
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/relation_embedders" with body:
    """
    {
      "anotherRelated": {
        "name": "Related"
      }
    }
    """
    Then the response status code should be 201
    And the header "Cache-Tags" should not exist

  Scenario: TagCollector can add cache tags for relations
    When I send a "GET" request to "/relation_embedders/2"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/related_dummies/1#thirdLevel,/related_dummies/1,/RE/2#anotherRelated,/RE/2#related,/RE/2"

  Scenario: Create resource with extraProperties on ApiProperty
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/extra_properties_on_properties" with body:
    """
    {
    }
    """
    Then the response status code should be 201
    And the header "Cache-Tags" should not exist

  Scenario: TagCollector can read propertyMetadata (tag is overriden with data from extraProperties)
    When I send a "GET" request to "/extra_properties_on_properties/1"
    Then the response status code should be 200
    And the header "Cache-Tags" should be equal to "/extra_properties_on_properties/1#overrideRelationTag,/extra_properties_on_properties/1"
