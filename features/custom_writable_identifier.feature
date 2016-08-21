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
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomWritableIdentifierDummy",
      "@id": "/custom_writable_identifier_dummies/my_slug",
      "@type": "CustomWritableIdentifierDummy",
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
          "name": "My Dummy"
        }
      ],
      "hydra:totalItems": 1
    }
    """

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
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomWritableIdentifierDummy",
      "@id": "/custom_writable_identifier_dummies/slug_modified",
      "@type": "CustomWritableIdentifierDummy",
      "name": "My Dummy modified"
    }
    """

  Scenario: API doc is correctly generated
    When I send a "GET" request to "/apidoc.jsonld"
    Then the response status code should be 200
    And the response should be in JSON
    And the hydra class "CustomWritableIdentifierDummy" exist
    And 3 operations are available for hydra class "CustomWritableIdentifierDummy"
    And 2 properties are available for hydra class "CustomWritableIdentifierDummy"
    And "name" property is readable for hydra class "CustomWritableIdentifierDummy"
    And "name" property is writable for hydra class "CustomWritableIdentifierDummy"
    And "slug" property is not readable for hydra class "CustomWritableIdentifierDummy"
    And "slug" property is writable for hydra class "CustomWritableIdentifierDummy"

  @dropSchema
  Scenario: Delete a resource
    When I send a "DELETE" request to "/custom_writable_identifier_dummies/slug_modified"
    Then the response status code should be 204
    And the response should be empty
