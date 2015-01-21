Feature: Create-Retrieve-Update-Delete
  In order to use an hypermedia API
  As a client software developer
  I need to be able to retrieve, create, update and delete JSON-LD encoded resources.

  Scenario: Create a resource
    Given I send a "POST" request to "/dummies" with body:
    """
    {
      "name": "My Dummy"
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
      "name": "My Dummy"
    }
    """

  Scenario: Get a resource
    Given I send a "GET" request to "/dummies/1"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    {
      "@context": "/contexts/Dummy",
      "@id": "/dummies/1",
      "@type": "Dummy",
      "name": "My Dummy"
    }
    """

  Scenario: Get a collection
    Given I send a "GET" request to "/dummies"
    Then the response status code should be 200
    And the response should be in JSON
    And the header "Content-Type" should be equal to "application/ld+json"
    And the JSON should be equal to:
    """
    [
      {
        "@context": "/contexts/Dummy",
        "@id":"/dummies/1",
        "@type":"Dummy",
        "name":"My Dummy"
      }
    ]
    """

  Scenario: Update a resource
      Given I send a "PUT" request to "/dummies/1" with body:
      """
      {
        "@id": "/dummies/1",
        "name": "A nice dummy"
      }
      """
      Then the response status code should be 202
      And the response should be in JSON
      And the header "Content-Type" should be equal to "application/ld+json"
      And the JSON should be equal to:
      """
      {
        "@context": "/contexts/Dummy",
        "@id": "/dummies/1",
        "@type": "Dummy",
        "name": "A nice dummy"
      }
      """

  Scenario: Delete a resource
    Given I send a "DELETE" request to "/dummies/1"
    Then the response status code should be 204
    And the response should be empty
