Feature: Validate incoming content type
  In order to have robust API
  As a client software developer
  The API must check incoming the content-type

  # It's not possible to omit the Content-Type with Behat. A unit test enforce that a 406 error code is returned in such case.

  Scenario: Send a document with a not supported content-type
    When I add "Content-Type" header equal to "text/plain"
    And I send a "POST" request to "/dummies" with body:
    """
    something
    """
    Then the response status code should be 406
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "hydra:description" should be equal to 'The content-type "text/plain" is not supported. Supported MIME types are "application/ld+json", "application/hal+json", "application/xml", "text/xml", "application/json", "text/html".'
