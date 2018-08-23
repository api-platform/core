Feature: JSON API pagination handling
  In order to be able to handle pagination
  As a client software developer
  I need to retrieve an JSON API pagination information as metadata and links

  Background:
    Given I add "Accept" header equal to "application/vnd.api+json"
    And I add "Content-Type" header equal to "application/vnd.api+json"

  @createSchema
  Scenario: Get the first page of a paginated collection according to basic config
    Given there are 10 dummy objects
    When I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data" should have 3 elements
    And the JSON node "meta.totalItems" should be equal to the number 10
    And the JSON node "meta.itemsPerPage" should be equal to the number 3
    And the JSON node "meta.currentPage" should be equal to the number 1

  Scenario: Get the fourth page of a paginated collection according to basic config
    When I send a "GET" request to "/dummies?page[page]=4"
    Then the JSON should be valid according to the JSON API schema
    And the JSON node "data" should have 1 elements
    And the JSON node "meta.currentPage" should be equal to the number 4

  Scenario: Get a paginated collection according to custom items per page in request
    When I send a "GET" request to "/dummies?page[itemsPerPage]=15"
    Then the response status code should be 200
    And the JSON should be valid according to the JSON API schema
    And the JSON node "data" should have 10 elements
    And the JSON node "meta.totalItems" should be equal to the number 10
    And the JSON node "meta.itemsPerPage" should be equal to the number 15
    And the JSON node "meta.currentPage" should be equal to the number 1

  Scenario: Get an error when provided page number is not valid
    When I send a "GET" request to "/dummies?page[page]=0"
    Then the response status code should be 400
