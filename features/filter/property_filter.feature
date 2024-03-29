Feature: Set properties to include
  In order to select specific properties from a resource
  As a client software developer
  I need to select attributes to retrieve

  @createSchema
  Scenario: Test properties filter
    Given there are 1 dummy objects with relatedDummy and its thirdLevel
    When I send a "GET" request to "/dummies/1?properties[]=name&properties[]=alias&properties[]=relatedDummy&properties[]=name_converted"
    Then the JSON node "name" should be equal to "Dummy #1"
    And the JSON node "alias" should be equal to "Alias #0"
    And the JSON node "relatedDummies" should not exist
    And the JSON node "name_converted" should exist

  Scenario: Test relation embedding
    When I send a "GET" request to "/dummies/1?properties[]=name&properties[]=alias&properties[relatedDummy][]=name"
    Then the JSON node "name" should be equal to "Dummy #1"
    And the JSON node "alias" should be equal to "Alias #0"
    And the JSON node "relatedDummy.name" should be equal to "RelatedDummy #1"
    And the JSON node "relatedDummies" should not exist

  Scenario: Test property filter on not resource relations
    When I send a "GET" request to "/dummy-with-array-of-objects/1?properties[notResourceObject][]=foo&properties[arrayOfNotResourceObjects][]=bar"
    Then the JSON node "notResourceObject.foo" should be equal to "foo"
    And the JSON node "notResourceObject.bar" should not exist
    And the JSON node "arrayOfNotResourceObjects[0].foo" should not exist
    And the JSON node "arrayOfNotResourceObjects[0].bar" should be equal to "bar"
    And the JSON node "id" should not exist
