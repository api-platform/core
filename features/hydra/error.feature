Feature: Error handling
  In order to be able to handle error client side
  As a client software developer
  I need to retrieve an Hydra serialization of errors

  Scenario: Get an error
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {}
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/ConstraintViolationList",
      "@type": "ConstraintViolationList",
      "hydra:title": "An error occurred",
      "hydra:description": "name: This value should not be blank.",
      "violations": [
        {
          "propertyPath": "name",
          "message": "This value should not be blank."
        }
      ]
    }
    """

  Scenario: Get an error during deserialization of simple relation
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Foo",
      "relatedDummy": {
        "name": "bar"
      }
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "hydra:Error"
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "hydra:description" should be equal to 'Nested documents for attribute "relatedDummy" are not allowed. Use IRIs instead.'
    And the JSON node "trace" should exist

  Scenario: Get an error during deserialization of collection
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Foo",
      "relatedDummies": [{
        "name": "bar"
      }]
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "hydra:Error"
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "hydra:description" should be equal to 'Nested documents for attribute "relatedDummies" are not allowed. Use IRIs instead.'
    And the JSON node "trace" should exist

  Scenario: Get an error because of an invalid JSON
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Foo",
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "hydra:Error"
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "hydra:description" should exist
    And the JSON node "trace" should exist

    Scenario: Get an error during update of an existing resource with a non-allowed update operation
      When I add "Content-Type" header equal to "application/ld+json"
      And I send a "POST" request to "/dummies" with body:
      """
      {
        "@id": "/dummies/1",
        "name": "Foo"
      }
      """
      Then the response status code should be 400
      And the response should be in JSON
      And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
      And the JSON node "@context" should be equal to "/contexts/Error"
      And the JSON node "@type" should be equal to "hydra:Error"
      And the JSON node "hydra:title" should be equal to "An error occurred"
      And the JSON node "hydra:description" should be equal to "Update is not allowed for this operation."
      And the JSON node "trace" should exist

    @createSchema
    Scenario: Populate database with related dummies. Check that id will be "/related_dummies/1"
      Given I add "Content-Type" header equal to "application/ld+json"
      And I send a "POST" request to "/related_dummies" with body:
      """
      {
          "@type": "https://schema.org/Product",
          "symfony": "laravel"
      }
      """
      Then the response status code should be 201
      And the response should be in JSON
      And the JSON node "@id" should be equal to "/related_dummies/1"
      And the JSON node "symfony" should be equal to "laravel"

    Scenario: Do not get an error during update of an existing relation with a non-allowed update operation
      When I add "Content-Type" header equal to "application/ld+json"
      And I send a "POST" request to "/relation_embedders" with body:
      """
      {
        "anotherRelated": {
          "@id": "/related_dummies/1",
          "@type": "https://schema.org/Product",
          "symfony": "phalcon"
        }
      }
      """
      Then the response status code should be 201
      And the response should be in JSON
      And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
      And the JSON node "@context" should be equal to "/contexts/RelationEmbedder"
      And the JSON node "@type" should be equal to "RelationEmbedder"
      And the JSON node "@id" should be equal to "/relation_embedders/1"
      And the JSON node "anotherRelated.@id" should be equal to "/related_dummies/1"
      And the JSON node "anotherRelated.symfony" should be equal to "phalcon"
