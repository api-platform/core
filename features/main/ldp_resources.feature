Feature: LDP Resources
  In order to use an API compliant with the LDP specification (https://www.w3.org/TR/ldp/)
  As a client software developer
  I must have some specific headers returned by the API

  @createSchema
  Scenario: Test Accept-Post and Allow headers for a LDP resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/dummy_get_post_delete_operations"
    Then the header "Accept-Post" should be equal to "text/turtle, application/ld+json"
    And the header "Allow" should be equal to "OPTIONS, HEAD, GET, POST, DELETE"
