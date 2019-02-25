Feature: Create-Retrieve-Update-Delete on abstract resource
  In order to use an hypermedia API
  As a client software developer
  I need to be able to retrieve, create, update and delete JSON-LD encoded resources even if they are abstract.

  @createSchema
  Scenario: Create a concrete resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/concrete_dummies" with body:
    """
    {
      "instance": "Concrete",
      "name": "My Dummy"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/concrete_dummies/1"
    And the header "Location" should be equal to "/concrete_dummies/1"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ConcreteDummy",
      "@id": "/concrete_dummies/1",
      "@type": "ConcreteDummy",
      "instance": "Concrete",
      "id": 1,
      "name": "My Dummy"
    }
    """

  Scenario: Get a resource
    When I send a "GET" request to "/abstract_dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ConcreteDummy",
      "@id": "/concrete_dummies/1",
      "@type": "ConcreteDummy",
      "instance": "Concrete",
      "id": 1,
      "name": "My Dummy"
    }
    """

  Scenario: Get a collection
    When I send a "GET" request to "/abstract_dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be valid according to this schema:
    """
    {
      "type": "object",
      "properties": {
        "hydra:member": {
          "type": "array",
          "items": {
            "type": "object",
            "properties": {
              "@type": {
                "type": "string",
                "pattern": "^ConcreteDummy$"
              },
              "instance": {
                "type": "string",
                "required": "true"
              }
            }
          },
          "minItems": 1
        }
      },
      "required": ["hydra:member"]
    }
    """

  Scenario: Update a concrete resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/concrete_dummies/1" with body:
      """
      {
        "@id": "/concrete_dummies/1",
        "instance": "Become real",
        "name": "A nice dummy"
      }
      """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/concrete_dummies/1"
    And the JSON should be equal to:
      """
      {
        "@context": "/contexts/ConcreteDummy",
        "@id": "/concrete_dummies/1",
        "@type": "ConcreteDummy",
        "instance": "Become real",
        "id": 1,
        "name": "A nice dummy"
      }
      """

  Scenario: Update a concrete resource using abstract resource uri
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "PUT" request to "/abstract_dummies/1" with body:
      """
      {
        "@id": "/concrete_dummies/1",
        "instance": "Become surreal",
        "name": "A nicer dummy"
      }
      """
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/concrete_dummies/1"
    And the JSON should be equal to:
      """
      {
        "@context": "/contexts/ConcreteDummy",
        "@id": "/concrete_dummies/1",
        "@type": "ConcreteDummy",
        "instance": "Become surreal",
        "id": 1,
        "name": "A nicer dummy"
      }
      """

  Scenario: Delete a resource
    When I send a "DELETE" request to "/abstract_dummies/1"
    Then the response status code should be 204
    And the response should be empty

  @createSchema
  Scenario: Create a concrete resource with discriminator
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/abstract_dummies" with body:
    """
    {
      "discr": "concrete",
      "instance": "Concrete",
      "name": "My Dummy"
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the header "Content-Location" should be equal to "/concrete_dummies/1"
    And the header "Location" should be equal to "/concrete_dummies/1"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ConcreteDummy",
      "@id": "/concrete_dummies/1",
      "@type": "ConcreteDummy",
      "instance": "Concrete",
      "id": 1,
      "name": "My Dummy"
    }
    """
