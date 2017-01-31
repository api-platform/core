Feature: Entity pagination with related items
  In order to retrieve custom data sets
  As a client software developer
  I need to retrieve paginated data even with relations

  @createSchema
  @dropSchema
  Scenario: Get a non-paginated collection
    Given there are 12 dummy objects each having 6 relatedDummies
    When I send a "GET" request to "/dummies?pagination=false"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member" should have 12 elements

  @createSchema
  @dropSchema
  Scenario: Get a non-paginated collection
    Given there are 12 dummy objects each having 6 relatedDummies
    When I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And print last JSON response
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:member" should have 12 elements
