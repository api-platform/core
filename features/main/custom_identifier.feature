Feature: Using custom identifier on resource
  In order to use an hypermedia API
  As a client software developer
  I need to be able to user other identifier than id in resources

  @createSchema
  Scenario: Create a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/custom_identifier_dummies" with body:
    """
    {
      "name": "My Dummy"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomIdentifierDummy",
      "@id": "/custom_identifier_dummies/1",
      "@type": "CustomIdentifierDummy",
      "customId": 1,
      "name": "My Dummy"
    }
    """

  Scenario: Get a resource
    When I send a "GET" request to "/custom_identifier_dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomIdentifierDummy",
      "@id": "/custom_identifier_dummies/1",
      "@type": "CustomIdentifierDummy",
      "customId": 1,
      "name": "My Dummy"
    }
    """

  Scenario: Get a collection
    When I send a "GET" request to "/custom_identifier_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomIdentifierDummy",
      "@id": "/custom_identifier_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/custom_identifier_dummies/1",
          "@type": "CustomIdentifierDummy",
          "customId": 1,
          "name": "My Dummy"
        }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: Update a resource
      When I add "Content-Type" header equal to "application/ld+json"
      And I send a "PUT" request to "/custom_identifier_dummies/1" with body:
      """
      {
        "name": "My Dummy modified"
      }
      """
      Then the response status code should be 200
      And the response should be in JSON
      And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
      And the JSON should be equal to:
      """
      {
        "@context": "/contexts/CustomIdentifierDummy",
        "@id": "/custom_identifier_dummies/1",
        "@type": "CustomIdentifierDummy",
        "customId": 1,
        "name": "My Dummy modified"
      }
      """

  Scenario: API doc is correctly generated
    When I send a "GET" request to "/docs.jsonld"
    Then the response status code should be 200
    And the response should be in JSON
    And the hydra class "CustomIdentifierDummy" exist
    And 3 operations are available for hydra class "CustomIdentifierDummy"
    And 1 properties are available for hydra class "CustomIdentifierDummy"
    And "name" property is readable for hydra class "CustomIdentifierDummy"
    And "name" property is writable for hydra class "CustomIdentifierDummy"

  @dropSchema
  Scenario: Delete a resource
    When I send a "DELETE" request to "/custom_identifier_dummies/1"
    Then the response status code should be 204
    And the response should be empty
