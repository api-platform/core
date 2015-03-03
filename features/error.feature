Feature: Error handling
  In order to be able to handle error client side
  As a client software developer
  I need to retrieve an Hydra serialization of errors

  Scenario: Get an error
    Given I send a "POST" request to "/dummies" with body:
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
      "hydra:description": "name: This value should not be blank.\n",
      "violations": [
        {
          "propertyPath": "name",
          "message": "This value should not be blank."
        }
      ]
    }
    """

  Scenario: Get an error during deserialization of simple relation
    Given I send a "POST" request to "/dummies" with body:
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
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Error",
      "@type": "Error",
      "hydra:title": "An error occurred",
      "hydra:description": "Type not supported (found \"array\" in attribute \"relatedDummy\")"
    }
    """

  Scenario: Get an error during deserialization of collection
    Given I send a "POST" request to "/dummies" with body:
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
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Error",
      "@type": "Error",
      "hydra:title": "An error occurred",
      "hydra:description": "Nested objects are not supported (found in attribute \"relatedDummies\")"
    }
    """

    Scenario: Get an error because of an invalid JSON
    Given I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "Foo",
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Error",
      "@type": "Error",
      "hydra:title": "An error occurred",
      "hydra:description": "Syntax error"
    }
    """
