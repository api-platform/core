Feature: Send security header
  In order to have secure API
  As a client software developer
  The API must send correct HTTP headers

  @createSchema
  @dropSchema
  Scenario: The API should always send a Content-Type header containing a charset
    When I send a "GET" request to "/dummies"
    Then the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
