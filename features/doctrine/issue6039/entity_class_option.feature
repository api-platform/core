Feature: Test entity class option on collections
  In order to retrieve a collections of resources mapped to a DTO automatically
  As a client software developer

  @createSchema
  @!mongodb
  Scenario: Get collection
    Given there are issue6039 users
    And I add "Accept" header equal to "application/ld+json"
    When I send a "GET" request to "/issue6039_user_apis"
    Then the response status code should be 200
    And the JSON node "hydra:member[0].bar" should not exist
