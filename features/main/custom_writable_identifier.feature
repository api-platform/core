Feature: Using custom writable identifier on resource
  In order to use an hypermedia API
  As a client software developer
  I need to be able to user other identifier than id in resource and set it via API call on POST / PUT.

  @createSchema
  Scenario: Create a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/custom_writable_identifier_dummies" with body:
    """
    {
      "name": "My Dummy",
      "slug": "my_slug"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/custom_writable_identifier_dummies/my_slug"
    And the header "Location" should be equal to "/custom_writable_identifier_dummies/my_slug"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomWritableIdentifierDummy",
      "@id": "/custom_writable_identifier_dummies/my_slug",
      "@type": "CustomWritableIdentifierDummy",
      "slug": "my_slug",
      "name": "My Dummy"
    }
    """

  Scenario: Get a resource
    When I send a "GET" request to "/custom_writable_identifier_dummies/my_slug"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomWritableIdentifierDummy",
      "@id": "/custom_writable_identifier_dummies/my_slug",
      "@type": "CustomWritableIdentifierDummy",
      "slug": "my_slug",
      "name": "My Dummy"
    }
    """

  Scenario: Get a collection
    When I send a "GET" request to "/custom_writable_identifier_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomWritableIdentifierDummy",
      "@id": "/custom_writable_identifier_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/custom_writable_identifier_dummies/my_slug",
          "@type": "CustomWritableIdentifierDummy",
          "slug": "my_slug",
          "name": "My Dummy"
        }
      ],
      "hydra:totalItems": 1
    }
    """

  @!mongodb
  Scenario: Update a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/custom_writable_identifier_dummies/my_slug" with body:
    """
    {
      "name": "My Dummy modified",
      "slug": "slug_modified"
    }
    """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/custom_writable_identifier_dummies/slug_modified"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomWritableIdentifierDummy",
      "@id": "/custom_writable_identifier_dummies/slug_modified",
      "@type": "CustomWritableIdentifierDummy",
      "slug": "slug_modified",
      "name": "My Dummy modified"
    }
    """

  Scenario: API docs are correctly generated
    When I send a "GET" request to "/docs.jsonld"
    Then the response status code should be 200
    And the response should be in JSON
    And the Hydra class "CustomWritableIdentifierDummy" exists
    And 4 operations are available for Hydra class "CustomWritableIdentifierDummy"
    And 2 properties are available for Hydra class "CustomWritableIdentifierDummy"
    And "name" property is readable for Hydra class "CustomWritableIdentifierDummy"
    And "name" property is writable for Hydra class "CustomWritableIdentifierDummy"
    And "slug" property is readable for Hydra class "CustomWritableIdentifierDummy"
    And "slug" property is writable for Hydra class "CustomWritableIdentifierDummy"

  @!mongodb
  Scenario: Delete a resource
    When I send a "DELETE" request to "/custom_writable_identifier_dummies/slug_modified"
    Then the response status code should be 204
    And the response should be empty
