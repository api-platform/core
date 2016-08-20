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
    And the header "Content-Type" should be equal to "application/ld+json"
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
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "Error"
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
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "Error"
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
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "Error"
    And the JSON node "hydra:title" should be equal to "An error occurred"
    And the JSON node "hydra:description" should exist
    And the JSON node "trace" should exist
