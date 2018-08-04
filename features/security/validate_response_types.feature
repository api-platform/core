Feature: Validate response types
  In order to have robust API
  As a client software developer
  The API must check the requested response type

  Scenario: Send a document without content-type
    When I add "Accept" header equal to "text/plain"
    And I send a "GET" request to "/dummies"
    Then the response status code should be 406
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the JSON node "detail" should be equal to 'Requested format "text/plain" is not supported. Supported MIME types are "application/ld+json", "application/hal+json", "application/vnd.api+json", "application/xml", "text/xml", "application/json", "text/html".'

  Scenario: Requesting a different format in the Accept header and in the URL should error
    When I add "Accept" header equal to "text/xml"
    And I send a "GET" request to "/dummies/1.json"
    Then the response status code should be 406
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the JSON node "detail" should be equal to 'Requested format "text/xml" is not supported. Supported MIME types are "application/json".'

  Scenario: Sending an invalid Accept header should error
    When I add "Accept" header equal to "invalid"
    And I send a "GET" request to "/dummies/1"
    Then the response status code should be 406
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the JSON node "detail" should be equal to 'Requested format "invalid" is not supported. Supported MIME types are "application/ld+json", "application/hal+json", "application/vnd.api+json", "application/xml", "text/xml", "application/json", "text/html".'

  Scenario: Requesting an invalid format in the URL should throw an error
    And I send a "GET" request to "/dummies/1.invalid"
    Then the response status code should be 404
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the JSON node "detail" should be equal to 'Format "invalid" is not supported'

  Scenario: Requesting an invalid format in the Accept header and in the URL should throw an error
    When I add "Accept" header equal to "text/invalid"
    And I send a "GET" request to "/dummies/1.invalid"
    Then the response status code should be 404
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the JSON node "detail" should be equal to 'Format "invalid" is not supported'
