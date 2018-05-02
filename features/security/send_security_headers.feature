Feature: Send security header
  In order to have secure API
  As a client software developer
  The API must send correct HTTP headers

  @createSchema
  Scenario: API responses must always contain security headers
    When I send a "GET" request to "/dummies"
    Then the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "X-Content-Type-Options" should be equal to "nosniff"
    And the header "X-Frame-Options" should be equal to "deny"

  Scenario: Exceptions responses must always contain security headers
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {"name": 1}
    """
    Then the response status code should be 400
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "X-Content-Type-Options" should be equal to "nosniff"
    And the header "X-Frame-Options" should be equal to "deny"

  @dropSchema
  Scenario: Error validation responses must always contain security headers
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {"name": ""}
    """
    Then the response status code should be 400
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "X-Content-Type-Options" should be equal to "nosniff"
    And the header "X-Frame-Options" should be equal to "deny"
