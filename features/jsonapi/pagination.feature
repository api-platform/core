Feature: JSON API pagination handling
  In order to be able to handle pagination
  As a client software developer
  I need to retrieve an JSON API pagination information as metadata and links

  @createSchema
  Scenario: Get a paginated collection according to basic config
    Given there is "10" dummy objects
    And I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And I save the response
    And I valide it with jsonapi-validator
    And the JSON node "data" should have 3 elements
    And the JSON node "meta.totalItems" should be equal to the number 10
    And the JSON node "meta.itemsPerPage" should be equal to the number 3
    And the JSON node "meta.currentPage" should be equal to the number 1
    And I send a "GET" request to "/dummies?page=4"
    And I save the response
    And I valide it with jsonapi-validator
    And the JSON node "data" should have 1 elements
    And the JSON node "meta.currentPage" should be equal to the number 4

  @dropSchema
  Scenario: Get a paginated collection according to custom items per page in request
    And I add "Accept" header equal to "application/vnd.api+json"
    And I send a "GET" request to "/dummies?itemsPerPage=15"
    Then the response status code should be 200
    And I save the response
    And I valide it with jsonapi-validator
    And the JSON node "data" should have 10 elements
    And the JSON node "meta.totalItems" should be equal to the number 10
    And the JSON node "meta.itemsPerPage" should be equal to the number 15
    And the JSON node "meta.currentPage" should be equal to the number 1
