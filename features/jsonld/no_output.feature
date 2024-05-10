Feature: Disable Id generation on anonymous resource collections

  @!mongodb
  Scenario: Post to an output false should not generate an IRI
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/no_iri_messages" with body:
    """
    {}
    """
    Then the response status code should be 202
