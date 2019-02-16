@postgres
Feature: Expose persisted object state
  In order to use an hypermedia API
  As a client software developer
  I need to be able to retrieve the exact state of resources after persistence.

  @!mongodb
  @createSchema
  Scenario: Create a resource with truncable value should return the correct object state
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/truncated_dummies" with body:
    """
    {
      "value": "20.3325"
    }
    """
    Then the response status code should be 201
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/TruncatedDummy",
      "@id": "/truncated_dummies/1",
      "@type": "TruncatedDummy",
      "value": "20.3",
      "id": 1
    }
    """

  @!mongodb
  Scenario: Update a resource with truncable value value should return the correct object state
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/truncated_dummies/1" with body:
    """
    {
      "value": "42.42"
    }
    """
    Then the response status code should be 200
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/TruncatedDummy",
      "@id": "/truncated_dummies/1",
      "@type": "TruncatedDummy",
      "value": "42.4",
      "id": 1
    }
    """
