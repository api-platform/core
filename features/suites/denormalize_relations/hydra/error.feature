Feature: Error handling
  In order to be able to handle error client side
  As a client software developer
  I need to retrieve an Hydra serialization of errors

  Scenario: Get an error
    When I send a "POST" request to "/dummies" with body:
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
    When I send a "POST" request to "/dummies" with body:
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
    And the JSON node "hydra:description" should be equal to 'Nested objects for attribute "relatedDummy" of "Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy" are not enabled. Use serialization groups to change that behavior.'
    And the JSON node "trace" should exist

  Scenario: Get an error during deserialization of collection
    When I send a "POST" request to "/dummies" with body:
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
    And the JSON node "hydra:description" should be equal to 'Nested objects for attribute "relatedDummies" of "Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\Dummy" are not enabled. Use serialization groups to change that behavior.'
    And the JSON node "trace" should exist

  Scenario: Get an error because of an invalid JSON
    When I send a "POST" request to "/dummies" with body:
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

  Scenario: I can't normalize unknown resources
    Given I send a "POST" request to "/related_dummies" with body:
    """
    {
      "unknown": "foo"
    }
    """
    Then the response status code should be 400
    And the response should be in JSON
    And the JSON node "@context" should be equal to "/contexts/Error"
    And the JSON node "@type" should be equal to "Error"
    And the JSON node "hydra:description" should be equal to 'IRI  not supported (found "foo" in "unknown" of "Dunglas\ApiBundle\Tests\Behat\TestBundle\Entity\RelatedDummy")'
