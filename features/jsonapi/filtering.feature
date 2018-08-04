Feature: JSON API filter handling
  In order to be able to handle filtering
  As a client software developer
  I need to be able to specify filtering parameters according to JSON API recommendation

  Background:
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/vnd.api+json"

  @createSchema
  Scenario: Apply filters based on the 'filter' query parameter with 'my' as value
    Given there are 30 dummy objects with dummyDate
    When I send a "GET" request to "/dummies?filter[name]=my"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data" should have 3 elements

  Scenario: Apply filters based on the 'filter' query parameter with 'foo' as value
    When I send a "GET" request to "/dummies?filter[name]=foo"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data" should have 0 elements

  Scenario: Apply property filter based on the 'fields'
    Given there are 2 dummy property objects
    When I send a "GET" request to "/dummy_properties?fields[DummyProperty]=id,foo,bar"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data" should have 2 elements
    And the JSON node "data[0].attributes._id" should be equal to "1"
    And the JSON node "data[0].attributes.foo" should be equal to "Foo #1"
    And the JSON node "data[0].attributes.bar" should be equal to "Bar #1"
    And the JSON node "data[0].attributes.group" should not exist

  Scenario: Apply filters based on the 'filter' query parameter with second level arguments
    When I send a "GET" request to "/dummies?filter[dummyDate][after]=2015-04-28"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data" should have 2 elements
