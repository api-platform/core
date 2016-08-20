Feature: Handle properly invalid data submitted to the API
  In order to have robust API
  As a client software developer
  The API must enforce strong typing

  @createSchema
  Scenario: Create a resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Not existing",
      "unsupported": true
    }
    """
    Then the response status code should be 201
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "description": null,
      "dummy": null,
      "dummyBoolean": null,
      "dummyDate": null,
      "dummyPrice": null,
      "relatedDummy": null,
      "relatedDummies": [],
      "jsonData": [],
      "name_converted": null,
      "name": "Not existing",
      "alias": null
    }
    """

  Scenario: Create a resource with wrong value type for relation
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Foo",
      "relatedDummy": "1"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "Error"
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "hydra:description" should be equal to 'Expected IRI or nested document for attribute "relatedDummy", "string" given.'
    And the JSON node "trace" should exist

  Scenario: Ignore invalid dates
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Invalid date",
      "dummyDate": "Invalid"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"

  Scenario: Send non-array data when an array is expected
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Invalid",
      "relatedDummies": "hello"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "Error"
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "hydra:description" should be equal to 'The type of the "relatedDummies" attribute must be "array", "string" given.'
    And the JSON node "trace" should exist

  Scenario: Send an object where an array is expected
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Invalid",
      "relatedDummies": {"a": {}, "b": {}}
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "Error"
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "hydra:description" should be equal to 'The type of the key "a" must be "int", "string" given.'

  @dropSchema
  Scenario: Send a scalar having the bad type
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": 42
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "Error"
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "hydra:description" should be equal to 'The type of the "name" attribute must be "string", "integer" given.'
