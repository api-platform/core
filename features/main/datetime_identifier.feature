Feature: Resource should generate an IRI for an item with a datetime identifier
  In order to use API resource
  As a developer
  I need to be able to create an item with a datetime identifier and have an IRI generated

  @createSchema
  Scenario: I should be able to POST a new entity
    When I add "Accept" header equal to "application/ld+json"
    And I add "Content-Type" header equal to "application/ld+json"
    When I send a "POST" request to "/entity_with_date_time_identifiers" with body:
    """
    {
      "day": "2022-05-22T14:38:16.164Z"
    }
    """
    Then the response status code should be 201
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/EntityWithDateTimeIdentifier",
      "@id": "/entity_with_date_time_identifiers/2022-05-22-14-38-16",
      "@type": "EntityWithDateTimeIdentifier",
      "day": "2022-05-22T14:38:16+00:00"
    }
    """
