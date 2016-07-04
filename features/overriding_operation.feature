Feature: Create-Retrieve-Update-Delete with a Overrode Operation context
  In order to use an hypermedia API
  As a client software developer
  I need to be able to retrieve, create, update and delete JSON-LD encoded resources.

  @createSchema
  Scenario: Create a resource
    When I send a "POST" request to "/overrode_operation_dummies" with body:
    """
    {
      "name": "My Overrode Operation Dummy",
      "description" : "Gerard",
      "alias": "notWritable"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/OverrodeOperationDummy",
      "@id": "/overrode_operation_dummies/1",
      "@type": "OverrodeOperationDummy",
      "name": "My Overrode Operation Dummy",
      "alias": null,
      "description": "Gerard"
    }
    """

  Scenario: Get a resource
    When I send a "GET" request to "/overrode_operation_dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/OverrodeOperationDummy",
      "@id": "/overrode_operation_dummies/1",
      "@type": "OverrodeOperationDummy",
      "name": "My Overrode Operation Dummy",
      "alias": null,
      "description": "Gerard"
    }
    """

  Scenario: Get a not found exception
    When I send a "GET" request to "/overrode_operation_dummies/42"
    Then the response status code should be 404

  Scenario: Get a collection
    When I send a "GET" request to "/overrode_operation_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
     "@context": "/contexts/OverrodeOperationDummy",
     "@id": "\/overrode_operation_dummies",
     "@type": "hydra:Collection",
     "hydra:member": [
          {
             "@id": "\/overrode_operation_dummies\/1",
             "@type": "OverrodeOperationDummy",
             "name": "My Overrode Operation Dummy",
             "alias": null,
             "description": "Gerard"
           }
        ],
       "hydra:totalItems": 1
      }
    """

  Scenario: Update a resource
    When I send a "PUT" request to "/overrode_operation_dummies/1" with body:
      """
      {
        "@id": "/overrode_operation_dummies/1",
        "name": "A nice dummy",
        "alias": "Dummy"
      }
      """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
      """
      {
        "@context": "/contexts/OverrodeOperationDummy",
        "@id": "/overrode_operation_dummies/1",
        "@type": "OverrodeOperationDummy",
        "alias": "Dummy",
        "description": "Gerard"
      }
      """

  Scenario: Get the final resource
    When I send a "GET" request to "/overrode_operation_dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/OverrodeOperationDummy",
      "@id": "/overrode_operation_dummies/1",
      "@type": "OverrodeOperationDummy",
      "name": "My Overrode Operation Dummy",
      "alias": "Dummy",
      "description": "Gerard"
    }
    """

  @dropSchema
  Scenario: Delete a resource
    When I send a "DELETE" request to "/overrode_operation_dummies/1"
    Then the response status code should be 204
    And the response should be empty
