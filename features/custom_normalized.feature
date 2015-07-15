Feature: Using custom normalized entity
  In order to use an hypermedia API
  As a client software developer
  I need to be able to filter correctly attribute of my entities

  @createSchema
  Scenario: Create a resource
    When I send a "POST" request to "/custom_normalized_dummies" with body:
    """
    {
      "name": "My Dummy",
      "alias": "My alias"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomNormalizedDummy",
      "@id": "/custom_normalized_dummies/1",
      "@type": "CustomNormalizedDummy",
      "name": "My Dummy",
      "alias": "My alias"
    }
    """

  Scenario: Get a resource
    When I send a "GET" request to "/custom_normalized_dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomNormalizedDummy",
      "@id": "/custom_normalized_dummies/1",
      "@type": "CustomNormalizedDummy",
      "name": "My Dummy",
      "alias": "My alias"
    }
    """

  Scenario: Get a collection
    When I send a "GET" request to "/custom_normalized_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/CustomNormalizedDummy",
      "@id": "/custom_normalized_dummies",
      "@type": "hydra:PagedCollection",
      "hydra:totalItems": 1,
      "hydra:itemsPerPage": 3,
      "hydra:firstPage": "/custom_normalized_dummies",
      "hydra:lastPage": "/custom_normalized_dummies",
      "hydra:member": [
        {
          "@id": "/custom_normalized_dummies/1",
          "@type": "CustomNormalizedDummy",
          "name": "My Dummy",
          "alias": "My alias"
        }
      ]
    }
    """

  Scenario: Update a resource
      When I send a "PUT" request to "/custom_normalized_dummies/1" with body:
      """
      {
        "name": "My Dummy modified"
      }
      """
      Then the response status code should be 200
      And the response should be in JSON
      And the header "Content-Type" should be equal to "application/ld+json"
      And the JSON should be equal to:
      """
      {
        "@context": "/contexts/CustomNormalizedDummy",
        "@id": "/custom_normalized_dummies/1",
        "@type": "CustomNormalizedDummy",
        "name": "My Dummy modified",
        "alias": "My alias"
      }
      """

  Scenario: API doc is correctly generated
    When I send a "GET" request to "/apidoc"
    Then the response status code should be 200
    And the response should be in JSON
    And the hydra class "CustomNormalizedDummy" exist
    And 3 operations are available for hydra class "CustomNormalizedDummy"
    And 2 properties are available for hydra class "CustomNormalizedDummy"
    And "name" property is readable for hydra class "CustomNormalizedDummy"
    And "name" property is writable for hydra class "CustomNormalizedDummy"
    And "alias" property is readable for hydra class "CustomNormalizedDummy"
    And "alias" property is writable for hydra class "CustomNormalizedDummy"

  @dropSchema
  Scenario: Delete a resource
    When I send a "DELETE" request to "/custom_normalized_dummies/1"
    Then the response status code should be 204
    And the response should be empty
