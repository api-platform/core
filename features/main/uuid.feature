Feature: Using uuid identifier on resource
  In order to use an hypermedia API
  As a client software developer
  I need to be able to user other identifier than id in resource and set it via API call on POST / PUT.

  @createSchema
  Scenario: Create a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/uuid_identifier_dummies" with body:
    """
    {
      "name": "My Dummy",
      "uuid": "41b29566-144b-11e6-a148-3e1d05defe78"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78"
    And the header "Location" should be equal to "/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78"

  Scenario: Get a resource
    When I send a "GET" request to "/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/UuidIdentifierDummy",
      "@id": "/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78",
      "@type": "UuidIdentifierDummy",
      "uuid": "41b29566-144b-11e6-a148-3e1d05defe78",
      "name": "My Dummy"
    }
    """

  Scenario: Get a collection
    When I send a "GET" request to "/uuid_identifier_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/UuidIdentifierDummy",
      "@id": "/uuid_identifier_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
          {
              "@id": "/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78",
              "@type": "UuidIdentifierDummy",
              "uuid": "41b29566-144b-11e6-a148-3e1d05defe78",
              "name": "My Dummy"
          }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: Update a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78" with body:
    """
    {
      "name": "My Dummy modified"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/UuidIdentifierDummy",
      "@id": "/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78",
      "@type": "UuidIdentifierDummy",
      "uuid": "41b29566-144b-11e6-a148-3e1d05defe78",
      "name": "My Dummy modified"
    }
    """

  Scenario: Create a resource with custom id generator
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/custom_generated_identifiers" with body:
    """
    {}
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/custom_generated_identifiers/foo"
    And the header "Location" should be equal to "/custom_generated_identifiers/foo"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomGeneratedIdentifier",
      "@id": "/custom_generated_identifiers/foo",
      "@type": "CustomGeneratedIdentifier",
      "id": "foo"
    }
    """

  Scenario: Delete a resource
    When I send a "DELETE" request to "/uuid_identifier_dummies/41b29566-144b-11e6-a148-3e1d05defe78"
    Then the response status code should be 204
    And the response should be empty

  @!mongodb
  @createSchema
  Scenario: Retrieve a resource identified by Ramsey\Uuid\Uuid
    Given there is a ramsey identified resource with uuid "41B29566-144B-11E6-A148-3E1D05DEFE78"
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/ramsey_uuid_dummies/41B29566-144B-11E6-A148-3E1D05DEFE78"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"

  @!mongodb
  Scenario: Delete a resource identified by a Ramsey\Uuid\Uuid
    When I send a "DELETE" request to "/ramsey_uuid_dummies/41B29566-144B-11E6-A148-3E1D05DEFE78"
    Then the response status code should be 204
    And the response should be empty

  @!mongodb
  Scenario: Retrieve a resource identified by a bad Ramsey\Uuid\Uuid
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/ramsey_uuid_dummies/41B29566-144B-E1D05DEFE78"
    Then the response status code should be 404
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
