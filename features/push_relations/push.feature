@sqlite
Feature: Push relations using HTTP/2
  In order to have a fast API
  As an API software developer
  I need to push relations using HTTP/2

  @createSchema
  Scenario: Push the relations of a collection of items
    Given there are 2 dummy objects with relatedDummy
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/dummies"
    Then the header "Link" should be equal to '</related_dummies/1>; rel="preload",</related_dummies/2>; rel="preload",<http://example.com/docs.jsonld>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"'

  Scenario: Push the relations of an item
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/dummies/1"
    Then the header "Link" should be equal to '</related_dummies/1>; rel="preload",<http://example.com/docs.jsonld>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"'
