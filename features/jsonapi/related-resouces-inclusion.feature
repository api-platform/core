Feature: JSON API Inclusion of Related Resources
  In order to be able to handle inclusion of related resources
  As a client software developer
  I need to be able to specify include parameters according to JSON API recommendation

  Background:
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/vnd.api+json"

  @createSchema
  @dropSchema
  Scenario: Request inclusion of a related resources on collection
    Given there are 3 dummy objects
    When I send a "GET" request to "/dummies/1?include=foo"
    Then the response status code should be 400
    And the response should be in JSON

