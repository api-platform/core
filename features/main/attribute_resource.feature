@php8
@v3
@!mysql
@!mongodb
Feature: Resource attributes
  In order to use the Resource attribute
  As a developer
  I should be able to fetch data from a state provider

  Scenario: Retrieve a Resource collection
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/attribute_resources"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/AttributeResources",
      "@id": "/attribute_resources",
      "@type": "hydra:Collection",
      "hydra:member": [
        {
          "@id": "/attribute_resources/1",
          "@type": "AttributeResource",
          "identifier": 1,
          "name": "Foo"
        },
        {
          "@id": "/attribute_resources/2",
          "@type": "AttributeResource",
          "identifier": 2,
          "name": "Bar"
        }
      ]
    }
    """

  Scenario: Retrieve the first resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/attribute_resources/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/AttributeResource",
      "@id": "/attribute_resources/1",
      "@type": "AttributeResource",
      "identifier": 1,
      "name": "Foo"
    }
    """

  Scenario: Retrieve the aliased resource
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "GET" request to "/dummy/1/attribute_resources/2"
    Then the response status code should be 301
    And the header "Location" should be equal to "/attribute_resources/2"
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/AttributeResource",
      "@id": "/attribute_resources/2",
      "@type": "AttributeResource",
      "identifier": 2,
      "dummy": "/dummies/1",
      "name": "Foo"
    }
    """

  Scenario: Patch the aliased resource
    When I add "Content-Type" header equal to "application/merge-patch+json"
    And I send a "PATCH" request to "/dummy/1/attribute_resources/2" with body:
    """
    {"name": "Patched"}
    """
    Then the response status code should be 301
    And the header "Location" should be equal to "/attribute_resources/2"
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json; charset=utf-8"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/AttributeResource",
      "@id": "/attribute_resources/2",
      "@type": "AttributeResource",
      "identifier": 2,
      "dummy": "/dummies/1",
      "name": "Patched"
    }
    """

  Scenario: Uri variables should be configured properly
    When I send a "GET" request to "/photos/1/resize/300/100"
    Then the response status code should be 400
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/problem+json; charset=utf-8"
    And the header "Link" should contain '<http://www.w3.org/ns/hydra/error>; rel="http://www.w3.org/ns/json-ld#error"'
    And the JSON node "hydra:description" should be equal to 'Unable to generate an IRI for the item of type "ApiPlatform\Tests\Fixtures\TestBundle\Entity\IncompleteUriVariableConfigured"'

  Scenario: Uri variables with Post operation
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/post_with_uri_variables_and_no_provider/{id}" with body:
    """
    {}
    """
    Then the response status code should be 201

  Scenario: Throw validation exception in a provider
    When I add "Content-Type" header equal to "application/ld+json"
    And I send a "POST" request to "/post_with_uri_variables/{id}" with body:
    """
    {}
    """
    Then the response status code should be 422

