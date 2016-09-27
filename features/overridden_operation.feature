Feature: Create-Retrieve-Update-Delete with a Overridden Operation context
  In order to use an hypermedia API
  As a client software developer
  I need to be able to retrieve, create, update and delete JSON-LD encoded resources.

  @createSchema
  Scenario: Create a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/overridden_operation_dummies" with body:
    """
    {
      "name": "My Overridden Operation Dummy",
      "description" : "Gerard",
      "alias": "notWritable"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/OverriddenOperationDummy",
      "@id": "/overridden_operation_dummies/1",
      "@type": "OverriddenOperationDummy",
      "name": "My Overridden Operation Dummy",
      "alias": null,
      "description": "Gerard"
    }
    """

  Scenario: Get a resource
    When I send a "GET" request to "/overridden_operation_dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/OverriddenOperationDummy",
      "@id": "/overridden_operation_dummies/1",
      "@type": "OverriddenOperationDummy",
      "name": "My Overridden Operation Dummy",
      "alias": null,
      "description": "Gerard"
    }
    """

  Scenario: Get a resource in XML
    When I add "Accept" header equal to "application/xml"
    And I send a "GET" request to "/overridden_operation_dummies/1"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/xml; charset=utf-8"
    And the response should be equal to
    """
    <?xml version="1.0"?>
    <response><name>My Overridden Operation Dummy</name><alias/><description>Gerard</description></response>
    """

  Scenario: Get a not found exception
    When I send a "GET" request to "/overridden_operation_dummies/42"
    Then the response status code should be 404

  Scenario: Get a collection
    When I send a "GET" request to "/overridden_operation_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/OverriddenOperationDummy",
      "@id": "/overridden_operation_dummies",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/overridden_operation_dummies/1",
          "@type": "OverriddenOperationDummy",
          "name": "My Overridden Operation Dummy",
          "alias": null,
          "description": "Gerard"
        }
      ],
      "hydra:totalItems": 1
    }
    """

  Scenario: Update a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/overridden_operation_dummies/1" with body:
      """
      {
        "@id": "/overridden_operation_dummies/1",
        "name": "A nice dummy",
        "alias": "Dummy"
      }
      """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
      """
      {
        "@context": "/contexts/OverriddenOperationDummy",
        "@id": "/overridden_operation_dummies/1",
        "@type": "OverriddenOperationDummy",
        "alias": "Dummy",
        "description": "Gerard"
      }
      """

  Scenario: Get the final resource
    When I send a "GET" request to "/overridden_operation_dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/OverriddenOperationDummy",
      "@id": "/overridden_operation_dummies/1",
      "@type": "OverriddenOperationDummy",
      "name": "My Overridden Operation Dummy",
      "alias": "Dummy",
      "description": "Gerard"
    }
    """

  @dropSchema
  Scenario: Delete a resource
    When I send a "DELETE" request to "/overridden_operation_dummies/1"
    Then the response status code should be 204
    And the response should be empty
