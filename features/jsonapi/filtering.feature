Feature: JSON API filter handling
  In order to be able to handle filtering
  As a client software developer
  I need to be able to specify filtering parameters according to JSON API recomendation

  @createSchema
  Scenario: Apply filters based on the 'filter' query parameter
    Given there is "30" dummy objects with dummyDate
    And I add "Accept" header equal to "application/vnd.api+json"
    When I send a "GET" request to "/dummies?filter[name]=my"
    Then the response status code should be 200
    And I save the response
    And I valide it with jsonapi-validator
    And the JSON node "data" should have 3 elements
    When I send a "GET" request to "/dummies?filter[name]=foo"
    Then the response status code should be 200
    And I save the response
    And I valide it with jsonapi-validator
    And the JSON node "data" should have 0 elements

  @dropSchema
  Scenario: Apply filters based on the 'filter' query parameter with second level arguments
    Given I add "Accept" header equal to "application/vnd.api+json"
    When I send a "GET" request to "/dummies?filter[dummyDate][after]=2015-04-28"
    Then the response status code should be 200
    And I save the response
    And I valide it with jsonapi-validator
    And the JSON node "data" should have 2 elements
