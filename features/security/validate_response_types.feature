Feature: Validate response types
  In order to have robust API
  As a client software developer
  The API must check the requested response type

  Scenario: Send a document without content-type
    When I add "Accept" header equal to "text/plain"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 406
    And the header "Content-Type" should be equal to "application/problem+json"
    And the JSON node "detail" should be equal to 'Requested format "text/plain" is not supported. Supported MIME types are "application/ld+json", "application/hal+json", "application/xml", "text/xml", "application/json".'
